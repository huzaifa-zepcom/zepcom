<?php

declare(strict_types=1);

namespace KitRma\Helper;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use KitFilterset\Helper\FiltersetTypes;
use KitRma\Content\RmaAddressBook\RmaAddressBookEntity;
use KitRma\Content\RmaCase\RmaCaseEntity;
use KitRma\Content\RmaStatus\RmaStatusEntity;
use KitRma\Content\RmaText\RmaTextCollection;
use KitRma\Content\RmaText\RmaTextEntity;
use KitRma\Content\RmaTicket\RmaTicketEntity;
use KitRma\Content\RmaTicketHistory\RmaTicketHistoryCollection;
use KitRma\KitRma;
use KitSupplier\Content\KitSupplier\KitSupplierEntity;
use Shopware\Core\Checkout\Document\GeneratedDocument;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Util\Random;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Mime\Email;

use const PATHINFO_EXTENSION;

trait TicketHelper
{
    public function sendMail(
        Context $context,
        MailTemplateEntity $mailTemplate,
        array $emailData
    ): ?Email {
        $data = new ParameterBag();
        if (!isset($emailData['recipients']) || empty($emailData['recipients'])) {
            // $shopEmail = 'info@klarsicht-it.de';
            $shopEmail = $this->configService->get('core.basicInformation.email');
            $shopName = $this->configService->get('core.basicInformation.shopName');
            $emailData['recipients'] = [
                $shopEmail => $shopName
            ];
        }

        $data->set('recipients', $emailData['recipients']);
        $data->set('senderName', $emailData['sender_name'] ?? $mailTemplate->getSenderName());
        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', strip_tags($mailTemplate->getContentPlain(), '<a>'));
        $data->set('subject', strip_tags($mailTemplate->getSubject()));

        if (isset($emailData['mediaIds'])) {
            foreach ($emailData['mediaIds'] as $id) {
                $media = $this->getMedia($id);
                if ($media) {
                    $attachments[] = $this->fileUploader->getMediaAsAttachment(
                        $media,
                        $context
                    );
                }
            }
        }

        if (!empty($attachments)) {
            $data->set('binAttachments', $attachments);
        }

        $languageId = $context->getLanguageId();
        $salesChannelCriteria = new Criteria();
        $salesChannelCriteria->addAssociation('mailHeaderFooter');
        $salesChannelCriteria->getAssociation('domains')->addFilter(new EqualsFilter('languageId', $languageId));

        $salesChannel = $this->container->get('sales_channel.repository')
            ->search($salesChannelCriteria, $context)
            ->first();

        $data->set('salesChannelId', $salesChannel->getId());

        return $this->mailService->send(
            $data->all(),
            $context,
            [
                'rma' => $emailData['rma'],
                'text' => isset($emailData['text']) ? $emailData['text'] : '',
                'salesChannel' => $salesChannel,
            ]
        );
    }

    public function getMedia(?string $mediaId): ?MediaEntity
    {
        if (empty($mediaId)) {
            return null;
        }

        $searchCriteria = new Criteria([$mediaId]);
        $mediaRepository = $this->container->get('media.repository');

        return $mediaRepository->search($searchCriteria, Context::createDefaultContext())->first();
    }

    public function getMailTemplate(
        Context $context,
        string $technicalName
    ): ?MailTemplateEntity {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);

        /** @var MailTemplateEntity|null $mailTemplate */
        $mailTemplate = $this->container->get('mail_template.repository')
            ->search($criteria, $context)
            ->first();

        return $mailTemplate;
    }

    /**
     * Helper method that creates an array of ticket that is used in the admin and also in the email.
     *
     * @param RmaTicketEntity $ticket
     * @param Context $context
     *
     * @return array
     */
    public function getTicketInfo(RmaTicketEntity $ticket, Context $context): array
    {
        $info = $ticket->getVars();
        $status = $ticket->getStatus();
        $case = $ticket->getCase();
        $customer = $ticket->getCustomer();
        $product = $ticket->getProduct();
        $supplier = $ticket->getSupplier();
        $order = $ticket->getOrder();
        $salutation = $customer ? $customer->getSalutation() : null;

        $info['admin_ticket_link'] = sprintf('%s/admin#/kit/rma/ticket/view/%s', getenv('APP_URL'), $ticket->getId());
        $info['rma_number'] = $ticket->getRmaNumber();
        $info['status_name'] = $status ? $status->getNameExt() : 'N/A';
        $info['customer_name'] = $customer ? $customer->getFirstName() . ' ' . $customer->getLastName() : '';
        $info['customernumber'] = $customer ? $customer->getCustomerNumber() : '';
        $info['customer_email'] = $ticket->getCustomerEmail() ?? ($customer !== null ? $customer->getEmail() : '');
        $info['product_name'] = $product ? $product->getName() : ($ticket->getProductName() ?? '');
        $info['articleordernumber'] = $product ? $product->getProductNumber() : ($ticket->getProductNumber() ?? '');
        $info['amount'] = $ticket->getAmount();
        $info['supplier'] = $supplier ? $supplier->getName() : '';
        $info['manufacturer_name'] = '';
        $info['warranty_info'] = '';
        $info['warranty_support'] = '';
        $info['warranty_hotline'] = '';
        $info['additional_info'] = $ticket->getAdditionalInfo() ?? '';

        $productId = $product ? $product->getId() : null;
        $manufacturer = $product ? $product->getManufacturer() : null;
        if ($manufacturer) {
            $info['manufacturer_name'] = $manufacturer->getName() ?? '';
            $customFields = $manufacturer->getCustomFields();
            $info['warranty_info'] = $customFields['kit_manufacturers_data_warrantyCondition'] ?? '';
            $info['warranty_support'] = $customFields['kit_manufacturers_data_support'] ?? '';
            $info['warranty_hotline'] = $customFields['kit_manufacturers_data_hotline'] ?? '';
        }

        $info['badges'] = $ticket->getBadges() ?? $this->getBadgeText($productId, $context);
        $info['case_name'] = $case ? $case->getName() : '';
        $info['ordernumber'] = $order ? $order->getOrderNumber() : '';
        $info['salutation'] = $salutation ? $salutation->getLetterName() : '';
        $info['order_date'] = $order ? $order->getOrderDate()->format('d.m.Y') : '';
        $info['supplier_address'] = $ticket->getDeliveryAddress() ?? '';
        $info['supplier_rma_number'] = $ticket->getSupplierRmaNumber() ?? '';
        $info['hash'] = $ticket->getHash();
        $info['link'] = $ticket->getLink();
        $info['url'] = sprintf('<a href="%s" target="_blank">%s</a>', $info['link'], $info['link']);
        $info['createdAt'] = $ticket->getCreatedAt();
        $info['created_on'] = $ticket->getCreatedAt();
        $info['ticket_content'] = $ticket->getTicketContent() ?? [];
        $info['additional'] = [];
        $info['files'] = [];

        // free text field content
        if ($info['ticket_content']) {
            foreach ($info['ticket_content'] as $key => &$value) {
                $value['name'] = $value['label'];
                if (($value['type'] === 'selectbox') && !is_array($value['values'])) {
                    $value['values'] = \array_map('trim', explode(',', $value['values']));
                }
                $info['additional'][] = $value;
            }
            unset($value);
        }

        $files = [];
        $mediaFiles = $ticket->getFiles();
        if ($mediaFiles) {
            foreach ($mediaFiles as $mediaFile) {
                $media = $this->getMedia($mediaFile['mediaId']);
                if ($media) {
                    $files[] = ['media' => $media, 'name' => $mediaFile['name']];
                }
            }
        }
        $info['files'] = $files;

        // Ticket History
        $history = [];
        $tmpHistory = $this->getTicketHistory($context, $ticket->getId());
        foreach ($tmpHistory->getElements() as $item) {
            $files = [];
            $historyItem = $item->getVars();
            $attachments = $item->getAttachment();
            if ($attachments) {
                foreach ($attachments as $attachment) {
                    $media = $this->getMedia($attachment['mediaId']);
                    if ($media) {
                        $files[] = ['media' => $media, 'name' => $attachment['name']];
                    }
                }
            }

            $historyItem['files'] = $files;
            $history[] = $historyItem;
        }

        $info['history'] = $history;
        $serialNumbers = [];
        foreach ($info['additional'] as $casefields) {
            if ($casefields['type'] === 'serial') {
                $serialNumbers[$casefields['name']] = Utility::trimString($casefields['value'], '-');
            }
        }

        $info['serials'] = $serialNumbers;

        return $info;
    }

    /**
     * @param Context $context
     * @param string $id
     * @param string|null $type
     *
     * @return RmaTicketHistoryCollection
     */
    public function getTicketHistory(
        Context $context,
        string $id,
        ?string $type = Utility::TYPE_EXTERNAL
    ): EntityCollection {
        $repo = $this->container->get('rma_ticket_history.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('ticketId', $id));

        if ($type !== null) {
            $criteria->addFilter(new EqualsFilter('type', $type));
        }

        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        return $repo->search($criteria, $context)->getEntities();
    }

    public function getCustomerNameFromOrder(Context $context, OrderEntity $order): string
    {
        /** @var EntityRepositoryInterface $repository */
        $addressRepository = $this->container->get('order_address.repository');

        /** @var OrderAddressEntity $address */
        $address = $addressRepository->search(new Criteria([$order->getBillingAddressId()]), $context)->first();

        return $address->getCompany() ?? $address->getFirstName() . ' ' . $address->getLastName();
    }

    public function getCreatedOnDate(): string
    {
        return (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
    }

    public function createLink(string $rmaNumber, string $hash): string
    {
        return sprintf("%s/rma/ticket/view?id=%s&hash=%s", getenv('APP_URL'), urlencode($rmaNumber), $hash);
    }

    public function createNewHash(string $rmaNumber): string
    {
        return md5(sprintf("%s%s", time(), $rmaNumber));
    }

    public function getCaseById(Context $context, string $id): ?RmaCaseEntity
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_case.repository');
        $criteria = new Criteria([$id]);
        $criteria->addSorting(new FieldSorting('name'));

        return $repository->search($criteria, $context)->getEntities()->first();
    }

    public function getCases(Context $context): array
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_case.repository');
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('name'));

        return $repository->search($criteria, $context)->getElements();
    }

    public function getText(Context $context, $id = null): ?RmaTextEntity
    {
        if ($id === null) {
            $id = $this->getConfig(null, 'ticketCreationText');
        }

        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_text.repository');

        return $repository->search(new Criteria([$id]), $context)->first();
    }

    public function getTicketCreationMessage(Context $context, array $data): string
    {
        $customername = $data['customer_name'];
        $productName = $data['product_name'];
        $ordernumber = $data['ordernumber'];
        $articleordernumber = $data['articleordernumber'];
        $rma_id = $data['rma_id'];
        $deeplink = $data['deeplink'];
        $supplier_address = $data['supplier_address'];
        $status_name = $data['status_name'];
        $text = $this->getText($context);
        $message = $text ? $text->getDescription() : '';
        // replace placeholder values
        $message = str_replace(
            [
                '{customer_name}',
                '{product_name}',
                '{ordernumber}',
                '{articleordernumber}',
                '{rma_id}',
                '{deeplink}',
                '{supplier_address}'
            ],
            [
                $customername,
                $productName,
                $ordernumber,
                $articleordernumber,
                $rma_id,
                $deeplink,
                $supplier_address,
            ],
            $message
        );

        $globalTexts = $this->getGlobalTexts($context);
        foreach ($globalTexts->getElements() as $gvar) {
            $message = str_replace(sprintf("{%s}", $gvar->getName()), $gvar->getDescription(), $message);
        }

        $message = str_replace(
            ['{rma_id}', '{deeplink}', '{status_name}'],
            [$rma_id, $deeplink, $status_name],
            $message
        );

        return nl2br($message);
    }

    public function getConfig(?string $salesChannelId, string $configKey, $default = null)
    {
        $configValue = $this->configService->get(sprintf('KitRma.config.%s', $configKey), $salesChannelId);
        if ($configValue === null || (is_string($configValue) && trim($configValue) === '')) {
            return $default;
        }

        return $configValue;
    }

    /**
     * @param Context $context
     *
     * @return RmaTextCollection
     */
    public function getGlobalTexts(Context $context): EntityCollection
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_text.repository');

        return $repository->search(
            (new Criteria())->addFilter(
                new EqualsFilter('type', 'G')
            ),
            $context
        )->getEntities();
    }

    public function getSupplier(int $supplierId, Context $context): ?KitSupplierEntity
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('kit_supplier.repository');

        return $repository->search((new Criteria())->addFilter(new EqualsFilter('supplierId', $supplierId)), $context)->first();
    }

    public function getAddress(?KitSupplierEntity $supplier, Context $context): ?RmaAddressBookEntity
    {
        if(!$supplier) return null;

        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_address_book.repository');

        return $repository->search(
            (new Criteria())->addFilter(
                new ContainsFilter('suppliers', $supplier->getId())
            ),
            $context
        )
            ->first();
    }

    public function getStatusByName(Context $context, string $name = 'Neu'): RmaStatusEntity
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_status.repository');

        return $repository->search((new Criteria())->addFilter(new EqualsFilter('name', $name)), $context)->first();
    }

    public function getStatusById(Context $context, ?string $id): RmaStatusEntity
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_status.repository');

        return $repository->search(new Criteria([$id]), $context)->first();
    }

    public function upsertTicket(array $ticketData, Context $context): void
    {
        /** @var EntityRepositoryInterface $repo */
        $repo = $this->container->get('rma_ticket.repository');

        $repo->upsert([$ticketData], $context);
    }

    public function addHistory(array $historyData, Context $ctx): void
    {
        /** @var EntityRepositoryInterface $repo */
        $repo = $this->container->get('rma_ticket_history.repository');

        $repo->upsert([$historyData], $ctx);
    }

    public function updateTicketStatus(array $data, Context $context): RmaTicketEntity
    {
        /** @var EntityRepositoryInterface $repo */
        $repo = $this->container->get('rma_ticket.repository');

        $repo->update([$data], $context);

        return $this->getTicketByRmaNumber($context, $data['rmaNumber']);
    }

    public function getTicketByRmaNumber(Context $context, $id): ?RmaTicketEntity
    {
        /** @var EntityRepositoryInterface $repo */
        $repo = $this->container->get('rma_ticket.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('rmaNumber', $id));
        $criteria = $this->addTicketAssociation($criteria);

        return $repo->search($criteria, $context)->getEntities()->first();
    }

    public function addTicketAssociation(Criteria $criteria): Criteria
    {
        $criteria->addAssociation('supplier');
        $criteria->addAssociation('customer');
        $criteria->addAssociation('customer.salutation');
        $criteria->addAssociation('order');
        $criteria->addAssociation('status');
        $criteria->addAssociation('product');
        $criteria->addAssociation('product.manufacturer');
        $criteria->addAssociation('case');

        return $criteria;
    }

    public function getBadgeText(?string $productId, Context $context): string
    {
        if ($productId) {
            $info = $this->filtersetHelper->getFiltersetByProduct($productId, $context);
            if ($info) {
                $badges = [];
                $fields = $info->getFields();
                if (isset($fields[FiltersetTypes::BADGE])) {
                    foreach ($fields[FiltersetTypes::BADGE] as $badgeInfo) {
                        $badges[] = $badgeInfo['kit_filterset_badges_text'];
                    }
                }
                if (isset($fields[FiltersetTypes::PRODUCT_NOTICE])) {
                    foreach ($fields[FiltersetTypes::PRODUCT_NOTICE] as $badgeInfo) {
                        $badges[] = $badgeInfo['kit_filterset_product_notice_text'];
                    }
                }

                if (array_filter($badges)) {
                    return implode('<br/><br/>', $badges);
                }
            }
        }

        return '';
    }

    public function getTodayRmaNumber(): string
    {
        return (new DateTimeImmutable())->format('Ymd');
    }

    public function getNextRmaNumber(): string
    {
        $today = (new DateTimeImmutable())->format('Y-m-d');
        $sql = <<<SQL
SELECT max(ticket_serial_number) as serial
FROM `rma_ticket` where created_at 
BETWEEN '%s 00:00:00' AND '%s 23:59:59'
SQL;

        $sql = sprintf($sql, $today, $today);
        $number = $this->connection->fetchOne($sql);

        return sprintf('0%d', ($number ? $number + 1 : 100));
    }

    public function createRmaNumber(): string
    {
        return sprintf('%s-%s', $this->getTodayRmaNumber(), $this->getNextRmaNumber());
    }

    private function convertImageToBase64(string $filename) {
        $baseUrl = sprintf('%s/%s/',
            $this->container->getParameter('shopware.filesystem.public.config.root'),
            KitRma::PDF_IMAGES_FOLDER
        );
        $file = $baseUrl . $filename;
        $type = pathinfo($file, PATHINFO_EXTENSION);
        $data = file_get_contents($file);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    public function generatePdfForTicket(Context $context, RmaTicketEntity $ticket, bool $isPreview = false): ?string
    {
        $ticketInfo = $this->getTicketInfo($ticket, $context);
        $rmaNumber = $ticketInfo['rmaNumber'];
        if (!$ticketInfo['product_name'] || !$ticketInfo['articleordernumber'] || !$ticketInfo['supplier_rma_number']) {
            throw new \Exception('Product and supplier number is required to generate PDF.');
        }

        $barcodeHtml = '';
        $serials = $ticketInfo['serials'];
        if ($serials) {
            foreach ($serials as $serial) {
                $barcodeHtml .= $serial . '&nbsp;';
            }
        }

        $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        $processedTemplate = file_get_contents(__DIR__ . '/../Warenbegleitschein/kit.rma.pdf.html');
        $template = str_replace(
            [
                '%header%',
                '%footer%',
                '%kunden%',
                '%datum%',
                '%uhrzeit%',
                '%adresse%',
                '%fehler%',
                '%vorgang%',
                '%rma%',
                '%orderNumber%',
                '%orderDate%',
                '%customerNumber%',
                '%date%',
                '%time%',
                '%address%',
                '%businessCase%',
                '%rmaNumber%',
                '%supplierRmaNumber%',
                '%productNumber%',
                '%productName%',
                '%quantity%',
                '%additionalInfo%',
                '%barcodes%'
            ],
            [
                $this->convertImageToBase64('header.png'),
                $this->convertImageToBase64('footer.png'),
                $this->convertImageToBase64('kunden-nr.png'),
                $this->convertImageToBase64('datum.png'),
                $this->convertImageToBase64('uhrzeit.png'),
                $this->convertImageToBase64('adresse.png'),
                $this->convertImageToBase64('fehler.png'),
                $this->convertImageToBase64('vorgang.png'),
                $this->convertImageToBase64('rma.png'),
                $ticketInfo['ordernumber'],
                $ticketInfo['order_date'],
                $ticketInfo['customernumber'],
                $now->format('d.m.Y'),
                $now->format('H:i'),
                nl2br($ticketInfo['supplier_address']),
                $ticketInfo['case_name'],
                $ticketInfo['rmaNumber'],
                $ticketInfo['supplier_rma_number'],
                $ticketInfo['articleordernumber'],
                Utility::truncate($ticketInfo['product_name']),
                $ticketInfo['amount'],
                $ticketInfo['additional_info'],
                $barcodeHtml
            ],
            $processedTemplate
        );

        // echo $template;die;
        $generatedDocument = new GeneratedDocument();
        $generatedDocument->setHtml($template);
        $pdf = $this->pdfGenerator->generate($generatedDocument);
        $generatedDocument->setFileBlob($pdf);

        if ($isPreview) {
            return $generatedDocument->getFileBlob();
        }

        $filename = sprintf("%s-%s", $rmaNumber, Random::getAlphanumericString(2));
        $mediaId = null;
        $mediaService = $this->mediaService;

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use (
            $mediaService,
            $filename,
            $pdf,
            &$mediaId
        ): void {
            $mediaId = $mediaService->saveFile(
                $pdf,
                $this->pdfGenerator->getExtension(),
                $this->pdfGenerator->getContentType(),
                $filename,
                $context,
                'KitRma',
                $mediaId,
                false
            );
        });

        return $mediaId;
    }
}
