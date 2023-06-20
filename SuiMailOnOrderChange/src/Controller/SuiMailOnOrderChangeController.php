<?php

declare(strict_types=1);

namespace SuiMailOnOrderChange\Controller;

use Exception;
use InvalidArgumentException;
use Shopware\Core\Checkout\Document\DocumentService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Content\Mail\Service\MailFactory;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

use function strip_tags;

/**
 * @RouteScope(scopes={"api"})
 */
class SuiMailOnOrderChangeController extends AbstractController
{
    private DataValidator $dataValidator;

    private StringTemplateRenderer $templateRenderer;

    private MailFactory $messageFactory;

    private AbstractMailSender $mailSender;

    private EntityRepositoryInterface $mediaRepository;

    private SalesChannelDefinition $salesChannelDefinition;

    private SystemConfigService $systemConfigService;

    private UrlGeneratorInterface $urlGenerator;

    private MediaService $mediaService;

    private EntityRepositoryInterface $documentRepository;

    private DocumentService $documentService;

    public function __construct(
        DataValidator $dataValidator,
        StringTemplateRenderer $templateRenderer,
        MailFactory $messageFactory,
        AbstractMailSender $mailSender,
        EntityRepositoryInterface $mediaRepository,
        SalesChannelDefinition $salesChannelDefinition,
        SystemConfigService $systemConfigService,
        UrlGeneratorInterface $urlGenerator,
        MediaService $mediaService,
        EntityRepositoryInterface $documentRepository,
        DocumentService $documentService
    ) {
        $this->dataValidator = $dataValidator;
        $this->templateRenderer = $templateRenderer;
        $this->messageFactory = $messageFactory;
        $this->mediaRepository = $mediaRepository;
        $this->salesChannelDefinition = $salesChannelDefinition;
        $this->systemConfigService = $systemConfigService;
        $this->urlGenerator = $urlGenerator;
        $this->mailSender = $mailSender;
        $this->mediaService = $mediaService;
        $this->documentRepository = $documentRepository;
        $this->documentService = $documentService;
    }

    /**
     * @Route("/api/_action/sui/order-status/mail", name="sui.order.status.mail", methods={"POST"},
     *     defaults={"XmlHttpRequest"=true})
     */
    public function mail(Request $request, Context $context): JsonResponse
    {
        $data = new ParameterBag();
        $mailTemplateId = $request->request->get('templateId');
        $orderId = $request->request->get('orderId');
        $isPreview = $request->request->get('preview');

        $mailTemplateCriteria = new Criteria([$mailTemplateId]);
        $mailTemplateCriteria->addAssociation('media.media');
        $mailTemplate = $this->container->get('mail_template.repository')
            ->search($mailTemplateCriteria, $context)
            ->first();

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('transactions');
        $criteria->addAssociation('deliveries.shippingMethod');
        $criteria->addAssociation('deliveries.positions.orderLineItem');
        $criteria->addAssociation('deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('deliveries.shippingOrderAddress.countryState');
        $criteria->addAssociation('transactions.stateMachineState');
        $criteria->addAssociation('transactions.paymentMethod');
        $criteria->addAssociation('orderCustomer.customer');
        $criteria->addAssociation('orderCustomer.salutation');
        $criteria->addAssociation('language');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('billingAddress.country');

        /** @var OrderEntity $order */
        $order = $this->container->get('order.repository')->search($criteria, $context)->first();
        $customer = $order->getOrderCustomer()->getCustomer();
        if (!$customer) {
            throw new InvalidArgumentException('Customer not found');
        }

        $customerEmail = $customer->getEmail();
        $salesChannelCriteria = new Criteria([$order->getSalesChannelId()]);
        $salesChannelCriteria->addAssociation('mailHeaderFooter');
        $salesChannelCriteria->addAssociation('translations');
        $salesChannelCriteria->getAssociation('domains');
        $salesChannelCriteria->addFilter(new EqualsFilter('languageId', $order->getLanguageId()));

        $salesChannel = $this->container->get('sales_channel.repository')
            ->search($salesChannelCriteria, $context)
            ->first();

        $senderEmail = $this->getSender($data->all(), $salesChannel->getId());
        $data->set('salesChannelId', $salesChannel->getId());
        $data->set('senderName', $mailTemplate->getSenderName());
        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', $mailTemplate->getContentPlain());
        $data->set('subject', strip_tags($mailTemplate->getSubject()));

        if ($request->request->get('toField')) {
            $customerEmail = $request->request->get('toField');
        }

        if ($request->request->get('content')) {
            $data->set('contentHtml', $request->request->get('content'));
            $data->set('contentPlain', strip_tags($request->request->get('content')));
        }
        if ($request->request->get('subject')) {
            $data->set('subject', $request->request->get('subject'));
        }

        $emailData = [
            'order' => $order,
            'customer' => $customer,
            'salesChannel' => $salesChannel,
            'customerGroup' => $customer->getGroup(),
            'recipients' => [
                $customerEmail => $customer->getFirstName() . ' ' . $customer->getLastName()
            ]
        ];

        $extension = new MailSendSubscriberConfig(
            $request->request->get('sendMail', true) === false,
            $request->request->get('documentIds', []),
            $request->request->get('mediaIds', [])
        );

        $attachments = $this->buildAttachments($context, $mailTemplate, $extension);

        if (!empty($attachments)) {
            $data->set('binAttachments', $attachments);
        }

        $data->set('mediaIds', []);
        $data->set('recipients', $emailData['recipients']);
        $message = $this->populateTemplate($data->all(), $context, $emailData);

        if ($message) {
            if ($isPreview) {
                $response = [
                    'toField' => $customerEmail,
                    'subject' => $message['subject'],
                    'content' => isset($message['content']) ? $message['content']['text/html'] : ''
                ];

                return new JsonResponse($response);
            }

            $mediaUrls = $this->getMediaUrls($message, $context);
            $binAttachments = $message['binAttachments'] ?? [];

            $swiftMessage = $this->messageFactory->create(
                $message['subject'],
                [$senderEmail => $message['senderName']],
                $emailData['recipients'],
                $message['content'],
                $mediaUrls,
                [],
                $binAttachments
            );

            if ($swiftMessage) {
                $this->enrichMessage($swiftMessage, $message);
                $this->mailSender->send($swiftMessage);

                $writes = array_map(static function ($id) {
                    return ['id' => $id, 'sent' => true];
                }, $extension->getDocumentIds());

                if (!empty($writes)) {
                    $this->documentRepository->update($writes, $context);
                }
            }
        }

        return new JsonResponse();
    }

    private function populateTemplate(array $data, Context $context, array $templateData = []): ?array
    {
        $definition = $this->getValidationDefinition($context);
        $this->dataValidator->validate($data, $definition);

        $salesChannel = $templateData['salesChannel'];
        $contents = $this->buildContents($data, $salesChannel);
        $this->templateRenderer->initialize();
        $template = $data['subject'];

        try {
            $data['subject'] = $this->templateRenderer->render($template, $templateData, $context);
            $template = $data['senderName'];
            $data['senderName'] = $this->templateRenderer->render($template, $templateData, $context);
            foreach ($contents as $index => $template) {
                $contents[$index] = $this->templateRenderer->render($template, $templateData, $context);
            }
        } catch (Exception $e) {
            $contents['text/html'] = "Could not render Mail-Template with error message:\n"
                . $e->getMessage() . "\n"
                . 'Error Code:' . $e->getCode() . "\n"
                . 'Template source:'
                . $template . "\n";
        }

        $data['content'] = $contents;

        return $data;
    }

    private function buildAttachments(
        Context $context,
        MailTemplateEntity $mailTemplate,
        MailSendSubscriberConfig $config
    ): array {
        $attachments = [];

        if ($mailTemplate->getMedia() !== null) {
            foreach ($mailTemplate->getMedia() as $mailTemplateMedia) {
                if ($mailTemplateMedia->getMedia() === null) {
                    continue;
                }
                $languageId = $mailTemplateMedia->getLanguageId();
                if ($languageId !== null && $languageId !== $context->getLanguageId()) {
                    continue;
                }

                $attachments[] = $this->mediaService->getAttachment(
                    $mailTemplateMedia->getMedia(),
                    $context
                );
            }
        }

        if (!empty($config->getDocumentIds())) {
            $criteria = new Criteria($config->getDocumentIds());
            $criteria->addAssociation('documentMediaFile');
            $criteria->addAssociation('documentType');

            $entities = $this->documentRepository->search($criteria, $context);

            foreach ($entities as $document) {
                $document = $this->documentService->getDocument($document, $context);

                $attachments[] = [
                    'content' => $document->getFileBlob(),
                    'fileName' => $document->getFilename(),
                    'mimeType' => $document->getContentType(),
                ];
            }
        }

        return $attachments;
    }

    private function getSender($data, ?string $salesChannelId): ?string
    {
        $senderEmail = $data['senderEmail'] ?? null;

        if ($senderEmail === null || trim($senderEmail) === '') {
            $senderEmail = $this->systemConfigService->get('core.basicInformation.email', $salesChannelId);
        }

        if ($senderEmail === null || trim($senderEmail) === '') {
            $senderEmail = $this->systemConfigService->get('core.mailerSettings.senderAddress', $salesChannelId);
        }

        if ($senderEmail === null || trim($senderEmail) === '') {
            return null;
        }

        return $senderEmail;
    }

    private function enrichMessage(Email $message, $data): void
    {
        if (isset($data['recipientsCc'])) {
            $message->to($data['recipientsCc']);
        }

        if (isset($data['recipientsBcc'])) {
            $message->bcc($data['recipientsBcc']);
        }

        if (isset($data['replyTo'])) {
            $message->replyTo($data['replyTo']);
        }

        if (isset($data['returnPath'])) {
            $message->returnPath($data['returnPath']);
        }
    }

    private function buildContents(array $data, ?SalesChannelEntity $salesChannel): array
    {
        if ($salesChannel && $mailHeaderFooter = $salesChannel->getMailHeaderFooter()) {
            return [
                'text/plain' => $mailHeaderFooter->getHeaderPlain() . $data['contentPlain'] .
                    $mailHeaderFooter->getFooterPlain(),
                'text/html' => $mailHeaderFooter->getHeaderHtml() . $data['contentHtml'] .
                    $mailHeaderFooter->getFooterHtml(),
            ];
        }

        return [
            'text/html' => $data['contentHtml'],
            'text/plain' => $data['contentPlain'],
        ];
    }

    private function getValidationDefinition(Context $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('mail_service.send');

        $definition->add('recipients', new NotBlank());
        $definition->add(
            'salesChannelId',
            new EntityExists(
                [
                    'entity' => $this->salesChannelDefinition->getEntityName(),
                    'context' => $context
                ]
            )
        );
        $definition->add('contentHtml', new NotBlank());
        $definition->add('contentPlain', new NotBlank());
        $definition->add('subject', new NotBlank());
        $definition->add('senderName', new NotBlank());

        return $definition;
    }

    private function getMediaUrls(array $data, Context $context): array
    {
        if (!isset($data['mediaIds']) || empty($data['mediaIds'])) {
            return [];
        }
        $criteria = new Criteria($data['mediaIds']);
        $media = null;
        $mediaRepository = $this->mediaRepository;
        $context->scope(
            Context::SYSTEM_SCOPE,
            static function (Context $context) use ($criteria, $mediaRepository, &$media): void {
                /** @var MediaCollection $media */
                $media = $mediaRepository->search($criteria, $context)->getElements();
            }
        );

        $urls = [];
        foreach ($media as $mediaItem) {
            $urls[] = $this->urlGenerator->getRelativeMediaUrl($mediaItem);
        }

        return $urls;
    }
}
