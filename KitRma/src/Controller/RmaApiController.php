<?php

declare(strict_types=1);

namespace KitRma\Controller;

use Doctrine\DBAL\Connection;
use Exception;
use KitFilterset\Helper\KitFiltersetHelper;
use KitRma\Helper\ErrorConstants;
use KitRma\Helper\FileUploader;
use KitRma\Helper\TicketHelper;
use KitRma\Helper\Utility;
use RuntimeException;
use Shopware\Core\Checkout\Document\FileGenerator\PdfGenerator;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use function nl2br;
use function sprintf;
use function str_replace;

/**
 * @RouteScope(scopes={"api"})
 */
class RmaApiController extends AbstractController
{
    use TicketHelper;

    private const WARENBEGLEITSCHEIN = 'Warenbegleitschein anhängen';
    private const NO_REPLY_EMAIL = 'info@klarsicht-it.de';

    /**
     * @var KitFiltersetHelper
     */
    protected $filtersetHelper;

    /**
     * @var FileUploader
     */
    protected $fileUploader;

    /**
     * @var AbstractMailService
     */
    protected $mailService;

    /**
     * @var SystemConfigService
     */
    protected $configService;

    /**
     * @var PdfGenerator
     */
    protected $pdfGenerator;

    /**
     * @var MediaService
     */
    protected $mediaService;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        FileUploader $fileUploader,
        AbstractMailService $mailService,
        SystemConfigService $configService,
        KitFiltersetHelper $filtersetHelper,
        PdfGenerator $pdfGenerator,
        MediaService $mediaService,
        Connection $connection
    ) {
        $this->fileUploader = $fileUploader;
        $this->mailService = $mailService;
        $this->configService = $configService;
        $this->filtersetHelper = $filtersetHelper;
        $this->pdfGenerator = $pdfGenerator;
        $this->mediaService = $mediaService;
        $this->connection = $connection;
    }

    /**
     * @Route("/api/_action/kit/ticket", name="api.action.kit.ticket", methods={"POST"},
     *     defaults={"XmlHttpRequest"=true})
     */
    public function ticket(Request $request, Context $context): JsonResponse
    {
        $isNew = false;
        $params = $request->request->all();
        $oldTicket = $params['old'] ?? [];
        $ticket = $ticketData = $params['ticket'];
        $userId = $context->getSource() instanceof AdminApiSource ? $context->getSource()->getUserId() : null;
        // if there is no hash, means it is created for the first time, so we also create the link and hash here

        if ($oldTicket) {
            if (empty($ticket['hash'])) {
                $isNew = true;
                $rmaNumber = $this->createRmaNumber();
                $ticket['rmaNumber'] = $rmaNumber;
                $ticket['ticketSerialNumber'] = (int)explode('-', $rmaNumber)[1];
                $ticket['hash'] = $this->createNewHash($rmaNumber);
                $ticket['link'] = $this->createLink($rmaNumber, $ticket['hash']);
                $ticket['userId'] = $userId;
            }
        } else {
            $rmaNumber = 'TEMP-' . Random::getInteger(10000, 999999);
            $ticket['rmaNumber'] = $rmaNumber;
        }

        $ticketContent = $ticket['ticketContent'] ?? $this->addSerialNumberInTicket($ticket, $context);

        if($ticketContent && \is_array($ticketContent)) {
            $serialNumbersContent = \array_filter($ticketContent, static function($tc) {
               return $tc['type'] === 'serial' && !empty($tc['value']);
            });
            $ticket['productSerialNumbers'] = \array_column($serialNumbersContent, 'value');
        }

        $ticket['ticketContent'] = $ticketContent;

        // unset irrelevant association data
        unset(
            $ticket['product'],
            $ticket['order'],
            $ticket['case'],
            $ticket['supplier'],
            $ticket['status'],
            $ticket['user'],
            $ticket['customer'],
            $ticket['serialNumbers']
        );

        $badgeText = isset($ticket['productId']) ? $this->getBadgeText($ticket['productId'], $context) : '';
        $ticket['badges'] = $ticket['badges'] ?? $badgeText;
        $this->upsertTicket($ticket, $context);
        $ticketEntity = $this->getTicketByRmaNumber($context, $ticket['rmaNumber']);
        if (!$ticketEntity) {
            throw new RuntimeException('No ticket found');
        }
        $info = $this->getTicketInfo($ticketEntity, $context);

        if ($isNew) {
            $mailTemplate = $this->getMailTemplate($context, 'kit.rma.external');
            if ($mailTemplate !== null) {
                $emailData = [
                    'rma' => $info,
                    'recipients' => [
                        $info['customer_email'] => $info['customer_name']
                    ]
                ];

                $this->sendMail($context, $mailTemplate, $emailData);
                $mailTemplate = null;
            }
        }

        $attachments = [];
        $mediaIds = [];
        if (isset($ticketData['attachments'])) {
            foreach ($ticketData['attachments'] as $value) {
                $attachments[] = $value;
                if ($value['name'] !== self::WARENBEGLEITSCHEIN) {
                    $mediaIds[] = $value['mediaId'];
                }
            }
        }

        if ($oldTicket && $oldTicket['statusId'] && $oldTicket['statusId'] !== $ticket['statusId']) {
            $oldStatus = $this->getStatusById($context, $oldTicket['statusId']);
            $newStatus = $this->getStatusById($context, $ticket['statusId']);
            $historyData = [
                'id' => Uuid::randomHex(),
                'ticketId' => $ticketData['id'],
                'statusId' => $newStatus->getId(),
                'read' => true,
                'sender' => Utility::SENDER_KLARSICHT,
                'type' => Utility::TYPE_INTERNAL,
                'userId' => $userId,
                'message' => sprintf(
                    'Das Ticket ist vom Status "%s" auf den Status "%s" gewechselt',
                    $oldStatus->getName(),
                    $newStatus->getName()
                ),
            ];

            $this->addHistory($historyData, $context);
        }

        if ($oldTicket && $oldTicket['caseId'] && $oldTicket['caseId'] !== $ticket['caseId']) {
            $oldCase = $this->getCaseById($context, $oldTicket['caseId']);
            // If the old case does exist
            if ($oldCase) {
                $newCase = $this->getCaseById($context, $ticket['caseId']);
                $caseMessage = 'Das Ticket hat sich von Geschäftsfall "%s" zu Geschäftsfall "%s" geändert <br/> %s';

                $table = '<table style="width:100%;">';
                $table .= '<caption>"' . $oldCase->getName() . '" case information</caption>';
                $table .= '<tr><th>Name</th><th>Value</th></tr>';

                foreach ($oldTicket['ticketContent'] as $content) {
                    $table .= '<tr style="padding: 3px;"><td>' . $content['label'] . '</td><td>' . $content['value'] .
                        '</td></tr>';
                }
                $table .= '</table>';

                $historyData = [
                    'id' => Uuid::randomHex(),
                    'ticketId' => $ticketData['id'],
                    'statusId' => $ticketEntity->getStatusId(),
                    'read' => true,
                    'sender' => Utility::SENDER_KLARSICHT,
                    'type' => Utility::TYPE_INTERNAL,
                    'userId' => $userId,
                    'message' => sprintf(
                        $caseMessage,
                        $oldCase->getName(),
                        $newCase->getName(),
                        $table
                    ),
                ];

                $this->addHistory($historyData, $context);
            }
        }

        // replace placeholder variables with their data.
        if (isset($ticketData['message']) && $ticketData['message']) {
            $message = str_replace(
                [
                    '{customer_name}',
                    '{product_name}',
                    '{ordernumber}',
                    '{articleordernumber}',
                    '{rma_id}',
                    '{supplier_rma_number}',
                    '{deeplink}',
                    '{status_name}',
                    '{supplier_address}',
                    '{manufacturer_name}',
                    '{warranty_info}',
                    '{warranty_support}',
                    '{warranty_hotline}',
                ],
                [
                    $info['customer_name'],
                    $info['product_name'],
                    $info['ordernumber'],
                    $info['articleordernumber'],
                    $info['rma_number'],
                    $info['supplier_rma_number'],
                    $info['link'],
                    $info['status_name'],
                    $info['supplier_address'],
                    $info['manufacturer_name'],
                    $info['warranty_info'],
                    $info['warranty_support'],
                    $info['warranty_hotline'],
                ],
                $ticketData['message']
            );

            $message = nl2br($message);
            // External communication to customer
            $historyData = [
                'id' => Uuid::randomHex(),
                'ticketId' => $ticketEntity->getId(),
                'statusId' => $ticketEntity->getStatusId(),
                'read' => true,
                'sender' => Utility::SENDER_KLARSICHT,
                'userId' => $userId,
                'type' => $ticketData['message_type'] ?? Utility::TYPE_INTERNAL,
                'attachment' => $attachments,
                'message' => $message,
            ];

            $this->addHistory($historyData, $context);

            if ($historyData['type'] === Utility::TYPE_EXTERNAL) {
                $mailTemplate = $this->getMailTemplate($context, 'kit.rma.external.answer');
                if ($mailTemplate !== null) {
                    $emailData = [
                        'rma' => $info,
                        'text' => $message,
                        'mediaIds' => $mediaIds,
                        'senderEmail' => self::NO_REPLY_EMAIL,
                        'recipients' => [
                            $ticketEntity->getCustomerEmail() => $info['customer_name'] ?: 'KLARSICHT IT',
                        ],
                    ];
                    $this->sendMail($context, $mailTemplate, $emailData);
                }
            }
            /* Do not send email for internal comments */
            // elseif ($historyData['type'] === Utility::TYPE_INTERNAL) {
            //     $mailTemplate = $this->getMailTemplate($context, 'kit.rma.internal.answer');
            //     if ($mailTemplate !== null) {
            //         $emailData = [
            //             'rma' => $info,
            //             'text' => $message,
            //             'senderEmail' => self::NO_REPLY_EMAIL,
            //             'mediaIds' => $mediaIds,
            //         ];
            //         $this->sendMail($context, $mailTemplate, $emailData);
            //     }
            // }
        }

        return new JsonResponse($info);
    }

    /**
     * @Route("/api/_action/kit/pdf", name="api.action.kit.pdf", methods={"POST"},
     *     defaults={"XmlHttpRequest"=true})
     */
    public function generatePdf(Request $request, Context $context): JsonResponse
    {
        $rmaNumber = (string)$request->request->get('rmaNumber');
        $ticket = $this->getTicketByRmaNumber($context, $rmaNumber);
        $mediaId = null;
        if ($ticket) {
            $mediaId = $this->generatePdfForTicket($context, $ticket);
        }

        return new JsonResponse(['mediaId' => $mediaId]);
    }

    private function addSerialNumberInTicket(array $ticket, Context $context): array
    {
        $case = $this->getCaseById($context, $ticket['caseId']);
        $serialNumbers = $ticket['serialNumbers'];
        if (!$serialNumbers) {
            return [];
        }

        $freetextFormValues = [];
        // If freetext fields are there, we loop through them to create the array. Each field can have
        // dependency on the line item amount, so we adjust the array accordingly.
        if ($case && $case->getFreetext()) {
            foreach ($case->getFreetext() as $key => $value) {
                $value['name'] = Utility::convertNameToFieldName($value['name']);
                if ($value['dependOnAmount']) {
                    for ($i = 0; $i < $ticket['amount']; $i++) {
                        $freetextFieldLabel = Utility::cleanString(sprintf('%s %d', $value['name'], $i + 1));
                        $value['label'] = $freetextFieldLabel;

                        if ($value['type'] === 'serial') {
                            $fieldValue = $serialNumbers[$i];
                            $value['value'] = $fieldValue;
                        }

                        $freetextFormValues[] = $value;
                    }
                } else {
                    $freetextFieldLabel = Utility::cleanString($value['name']);
                    $value['label'] = $freetextFieldLabel;

                    if ($value['type'] === 'serial') {
                        $fieldValue = $serialNumbers[0];
                        $value['value'] = $fieldValue;
                    }

                    $freetextFormValues[] = $value;
                }
            }
        }

        return $freetextFormValues;
    }
}
