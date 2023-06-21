<?php

declare(strict_types=1);

namespace KitRma\Controller;

use Doctrine\DBAL\Connection;
use Exception;
use KitFilterset\Helper\KitFiltersetHelper;
use KitRma\Content\RmaStatus\RmaStatusEntity;
use KitRma\Content\RmaTicket\RmaTicketEntity;
use KitRma\Helper\ErrorConstants;
use KitRma\Helper\FileUploader;
use KitRma\Helper\TicketHelper;
use KitRma\Helper\Utility;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Document\FileGenerator\PdfGenerator;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\PluginNotActivatedException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoader;
use Shopware\Storefront\Page\Account\Overview\AccountOverviewPageLoader;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use function array_merge;
use function preg_replace;

/**
 * @RouteScope(scopes={"storefront"})
 */
class RmaController extends StorefrontController
{
    use TicketHelper;

    /**
     * @var KitFiltersetHelper
     */
    protected $filtersetHelper;

    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @var AbstractMailService
     */
    private $mailService;

    /**
     * @var SystemConfigService
     */
    private $configService;

    /**
     * @var AccountLoginPageLoader
     */
    private $genericLoader;

    /**
     * @var AccountOverviewPageLoader
     */
    private $accountOverViewPageLoader;

    /**
     * @var PdfGenerator
     */
    private $pdfGenerator;

    /**
     * @var MediaService
     */
    private $mediaService;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        FileUploader $fileUploader,
        AbstractMailService $mailService,
        SystemConfigService $configService,
        KitFiltersetHelper $filtersetHelper,
        AccountLoginPageLoader $genericLoader,
        AccountOverviewPageLoader $accountOverViewPageLoader,
        PdfGenerator $pdfGenerator,
        MediaService $mediaService,
        Connection $connection
    ) {
        $this->fileUploader = $fileUploader;
        $this->mailService = $mailService;
        $this->configService = $configService;
        $this->filtersetHelper = $filtersetHelper;
        $this->genericLoader = $genericLoader;
        $this->accountOverViewPageLoader = $accountOverViewPageLoader;
        $this->pdfGenerator = $pdfGenerator;
        $this->mediaService = $mediaService;
        $this->connection = $connection;
    }

    /**
     * @Route("/rma", name="frontend.rma.index", options={"seo"="false"}, methods={"GET", "POST"})
     */
    public function indexRoute(Request $request, SalesChannelContext $context): Response
    {
        // Initialize the data array
        $data['page'] = $this->getPageData($request, $context);
        $data['loggedIn'] = false;

        // Check if the KitRma plugin is active for the current sales channel
        if (!$this->getConfig($context->getSalesChannel()->getId(), 'active')) {
            throw new PluginNotActivatedException('KitRma');
        }

        // Handle error parameter in the request
        if ($request->get('error')) {
            return $this->renderStorefront(
                '@KitRma/rma/index.html.twig',
                [
                    'page' => $this->getPageData($request, $context),
                    'error' => $request->get('error')
                ]
            );
        }

        // Check if the customer is logged in
        if ($context->getCustomer()) {
            $data['loggedIn'] = true;

            // Retrieve the RMA statuses
            $statuses = $this->getRmaStatus($context->getContext());

            // Prepare the search status and status array
            $searchStatus = $statusArray = [];

            /** @var RmaStatusEntity $status */
            foreach ($statuses as $status) {
                $searchStatus[$status->getNameExt()][] = $status->getId();
            }

            foreach ($searchStatus as $name => $ids) {
                $statusArray[implode(',', $ids)] = $name;
            }

            $data['statuses'] = $statusArray;

            // Retrieve the RMA tickets for the customer
            $rmaTickets = $this->getRmaTickets($request, $context->getCustomer(), $context->getContext());

            $data['rmaTickets'] = $rmaTickets;
        }

        // Render the storefront view with the data
        return $this->renderStorefront('@KitRma/rma/index.html.twig', $data);
    }


    public function getPageData(Request $request, SalesChannelContext $context)
    {
        if ($context->getCustomer()) {
            $page = $this->accountOverViewPageLoader->load($request, $context, $context->getCustomer());
        } else {
            $page = $this->genericLoader->load($request, $context);
        }

        return $page;
    }

    private function getRmaStatus(Context $context): EntityCollection
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_status.repository');
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('nameExt', FieldSorting::DESCENDING));

        return $repository->search($criteria, $context)->getEntities();
    }

    private function getRmaTickets(Request $request, CustomerEntity $customer, Context $context): EntityCollection
    {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->container->get('rma_ticket.repository');
        $criteria = new Criteria();
        $criteria = $this->addTicketAssociation($criteria);
        $criteria->addFilter(new EqualsFilter('customer.customerNumber', $customer->getCustomerNumber()));
        $statusId = $request->get('status_id');

        if ($statusId) {
            $status = explode(',', $statusId);
            if (!is_array($status)) {
                $status = [$statusId];
            }
            $criteria->addFilter(new EqualsAnyFilter('statusId', $status));
        } else {
            $criteria->addFilter(new NotFilter('AND', [new EqualsFilter('statusId', null)]));
        }

        return $repository->search($criteria, $context)->getEntities();
    }

    /**
     * @Route("/rma/search", name="frontend.rma.search", options={"seo"="false"}, methods={"GET","POST"})
     */
    public function searchRoute(Request $request, SalesChannelContext $context): Response
    {
        // Check if the KitRma plugin is active for the current sales channel
        if (!$this->getConfig($context->getSalesChannel()->getId(), 'active')) {
            throw new RouteNotFoundException();
        }

        // Merge request data from POST and GET methods
        $data = $request->request->all();
        $data = array_merge($data, $request->query->all());

        // Get page data
        $data['page'] = $this->getPageData($request, $context);

        // Get the context
        $ctx = $context->getContext();

        // Get the order criteria based on the data
        $criteria = $this->getOrderCriteria($data);

        // Get the order repository
        $orderRepository = $this->container->get('order.repository');

        // Search for the order based on the criteria
        /** @var OrderEntity $order */
        $order = $orderRepository->search($criteria, $ctx)->first();

        // If the order is not found, show an error message
        if ($order === null) {
            return $this->showError(ErrorConstants::ORDER_NOT_FOUND);
        }

        // Set the order ID, order number, and customer name in the data
        $data['orderId'] = $order->getId();
        $data['ordernumber'] = $order->getOrderNumber();
        $data['customername'] = $this->getCustomerNameFromOrder($context->getContext(), $order);

        // Store the data in the session
        $request->getSession()->set('kitRma', $data);

        // Get the line items of the order
        $lineItems = $order->getLineItems();

        // Add the line items to the data
        $data['products'] = $lineItems;

        // Render the storefront view with the data
        return $this->renderStorefront('@KitRma/rma/search.html.twig', $data);
    }


    private function getOrderCriteria(array $data): Criteria
    {
        $criteria = new Criteria();
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAssociation('orderCustomer.customer');
        $criteria->addAssociation('orderCustomer.customer.group');
        $criteria->addAssociation('lineItems.product.categories');
        $criteria->addAssociation('lineItems.product.manufacturer');
        $criteria->addAssociation('lineItems.product.customFields');
        $criteria->addAssociation('addresses');

        if (isset($data['orderId'])) {
            $criteria->setIds([$data['orderId']]);
        } else {
            $criteria->addFilter(new EqualsFilter('orderNumber', $data['ordernumber']));
        }

        if (isset($data['zipcode'])) {
            $criteria->addFilter(new EqualsFilter('addresses.zipcode', $data['zipcode']));
        }

        return $criteria;
    }

    private function showError(string $error): Response
    {
        return $this->forwardToRoute('frontend.rma.index', ['error' => $error]);
    }

    /**
     * @Route("/rma/complain", name="frontend.rma.complain", options={"seo"="false"}, methods={"GET", "POST"})
     */
    public function complainRoute(Request $request, SalesChannelContext $context): ?Response
    {
        // Check if the KitRma plugin is active for the current sales channel
        if (!$this->getConfig($context->getSalesChannel()->getId(), 'active')) {
            throw new RouteNotFoundException();
        }

        // Get the context and retrieve the session data for kitRma
        $ctx = $context->getContext();
        $data = $request->getSession()->get('kitRma', []);

        // Get page data
        $data['page'] = $this->getPageData($request, $context);

        // Get the product ID from the request
        $productId = $request->get('productId');

        // Get the order repository and criteria
        $orderRepository = $this->container->get('order.repository');
        $criteria = $this->getOrderCriteria($data);

        // Search for the order based on the criteria
        /** @var OrderEntity $order */
        $order = $orderRepository->search($criteria, $ctx)->first();

        // If the product ID or line items are not available, show an error message
        if (!$productId || !$order->getLineItems()) {
            return $this->showError(ErrorConstants::PRODUCT_NOT_EXISTS);
        }

        // Set the order ID and order number in the data
        $data['orderId'] = $order->getId();
        $data['ordernumber'] = $order->getOrderNumber();

        // Get the line item from the order based on the product ID
        $orderItem = $this->getLineItemFromProductId($order, $productId);
        if (!$orderItem) {
            $lineItem = $order->getLineItems()->get($productId);
        } else {
            $lineItem = $order->getLineItems()->filterByProperty('productId', $productId)->first();
        }

        // If the line item is not found, show an error message
        if (!$lineItem) {
            return $this->showError(ErrorConstants::PRODUCT_NOT_EXISTS);
        }

        // Set the product name, product ID, and quantity in the data
        $data['productName'] = $lineItem ? $lineItem->getLabel() : '';
        $data['productId'] = $lineItem->getProductId() ?? $lineItem->getId();
        $data['qty'] = $lineItem->getQuantity();

        // Check if a ticket already exists for the order and line item
        $ticket = $this->getTicketByIds($context->getContext(), $order->getId(), $lineItem);

        // If a ticket exists, redirect to the ticket view
        if ($ticket) {
            $hash = $ticket->getHash();
            $status = $ticket->getStatus();
            $params = [
                'id' => $ticket->getRmaNumber(),
                'hash' => $hash,
                'ordernumber' => $ticket->getOrder() ? $ticket->getOrder()->getOrderNumber() : '',
                'endState' => !$status || $status->isEndstateFinal()
            ];

            return $this->forwardToRoute('frontend.rma.ticket.view', $params);
        }

        // Get the cases and add them to the data
        $cases = $this->getCases($context->getContext());
        $data['cases'] = $cases;

        // Render the storefront view with the data
        return $this->renderStorefront('@KitRma/rma/complain.html.twig', $data);
    }

    private function getLineItemFromProductId(OrderEntity $order, $productId): ?OrderLineItemEntity
    {
        $products = $order->getLineItems();
        if ($products) {
            foreach ($products->getElements() as $element) {
                if ($element->getProductId() === $productId) {
                    return $element;
                }
            }
        }

        return null;
    }

    private function getTicketByIds(Context $context, string $orderId, OrderLineItemEntity $orderItem): ?RmaTicketEntity
    {
        /** @var EntityRepositoryInterface $repo */
        $repo = $this->container->get('rma_ticket.repository');

        $criteria = new Criteria();
        $criteria = $this->addTicketAssociation($criteria);
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addFilter(new EqualsFilter('status.endstateFinal', false));
        if ($orderItem->getProductId()) {
            $criteria->addFilter(new EqualsFilter('productId', $orderItem->getProductId()));
        } else {
            $criteria->addFilter(new EqualsFilter('productName', $orderItem->getLabel()));
        }

        return $repo->search($criteria, $context)->getEntities()->first();
    }

    /**
     * @Route("/rma/ticket", name="frontend.rma.ticket", options={"seo"="false"}, methods={"POST"})
     */
    public function ticketRoute(Request $request, SalesChannelContext $context): Response
    {
        $data = $request->request->all();
        $data = array_merge($request->getSession()->get('kitRma'), $data);
        $data['amount'] = $amount = (int)$data['amount'];
        $data['page'] = $this->getPageData($request, $context);
        $caseId = $data['case_id'] ?? null;
        $productId = $data['productId'];
        $customerId = null;
        $product = null;
        $customerEmail = '';
        $orderId = null;
        $supplierId = null;
        $ordernumber = '';
        $customername = '';
        $returnAddress = '';
        $productName = '';
        $rmaId = $this->createRmaNumber();

        // Get the case by ID
        $case = $this->getCaseById($context->getContext(), $caseId);
        if ($case) {
            $data['case'] = $case;
            // If freetext is not already sent and the case has freetext configured, we show an additional step
            if (!empty($case->getFreetext()) && !$request->get('freetextform')) {
                $data['freetextfields'] = $case->getFreetext();
                $request->getSession()->set('kitRma', $data);

                return $this->forwardToRoute('frontend.rma.complain', $data);
            }
        }

        $ctx = $context->getContext();
        $ticket = null;
        $criteria = $this->getOrderCriteria($data);
        $orderRepository = $this->container->get('order.repository');

        // Search for the order based on the criteria
        /** @var OrderEntity $order */
        $order = $orderRepository->search($criteria, $ctx)->first();

        // If the order is found
        if ($order) {
            $orderCustomer = $order->getOrderCustomer();

            // If line items are not available or empty, show an error message
            if (!$order->getLineItems() || !$order->getLineItems()->count()) {
                return $this->showError(ErrorConstants::PRODUCT_NOT_FOUND);
            }

            // Get the line item from the order based on the product ID
            $orderItem = $order->getLineItems()->filterByProperty('productId', $productId)->first();
            if (!$orderItem) {
                $orderItem = $order->getLineItems()->get($productId);
            }

            // If the line item is not found, show an error message
            if (!$orderItem) {
                return $this->showError(ErrorConstants::PRODUCT_NOT_EXISTS);
            }

            // Check if the requested amount exceeds the available quantity of the line item
            if ($orderItem->getQuantity() < $amount) {
                return $this->showError(ErrorConstants::PRODUCT_AMOUNT);
            }

            // Get the customer from the order
            if (!$orderCustomer) {
                return $this->showError(ErrorConstants::CUSTOMER_NOT_FOUND);
            }

            $customer = $orderCustomer->getCustomer();
            if (!$customer) {
                return $this->showError(ErrorConstants::CUSTOMER_NOT_FOUND);
            }

            // Get the product and set relevant data
            $product = $orderItem->getProduct();
            $data['product'] = $product;
            $productName = $product ? $product->getName() : $orderItem->getLabel();
            $data['productName'] = $productName;
            $data['productId'] = $productId;

            $customerId = $customer->getId();
            $customerEmail = $customer->getEmail();
            $customername = $customer->getCompany() ?? $customer->getFirstName() . " " . $customer->getLastName();
            $orderId = $order->getId();
            $ordernumber = $order->getOrderNumber();
            $ticket = $this->getTicketByIds($context->getContext(), $orderId, $orderItem);

            // Get the supplier ID and return address
            $productCustomFields = $product ? $product->getCustomFields() : [];
            $supplierId = $productCustomFields['kit_product_supplier_id'] ?? null;
            if ($supplierId) {
                $supplier = $this->getSupplier($supplierId, $ctx);
                $supplierId = $supplier ? $supplier->getId() : null;
                $rmaAddress = $this->getAddress($supplier, $ctx);
                $returnAddress = $rmaAddress ? $rmaAddress->getAddress() : '';
            }
        }

        // If a ticket already exists for the order and line item, redirect to the ticket view
        if ($ticket) {
            $hash = $ticket->getHash();
            if ($request->get('id') &&
                ($ticket->getRmaNumber() !== $request->get('id') && $request->get('hash') !== $hash)) {
                return $this->showError(ErrorConstants::ORDER_NOT_FOUND);
            }

            $status = $ticket->getStatus();
            $params = [
                'id' => $ticket->getRmaNumber(),
                'hash' => $hash,
                'ordernumber' => $order ? $order->getOrderNumber() : '',
                'endState' => !$status || $status->isEndstateFinal()
            ];

            return $this->forwardToRoute('frontend.rma.ticket.view', $params);
        }

        $data['customername'] = $customername;
        $data['ordernumber'] = $ordernumber;
        $data['rma_number'] = $rmaId;

        // Get the new ticket status
        $newStatusId = $this->getConfig($context->getSalesChannel()->getId(), 'ticketCreationStatus');
        $status = $this->getStatusById($ctx, $newStatusId);
        $hash = $this->createNewHash($rmaId);
        $link = $this->createLink($rmaId, $hash);
        $url = sprintf('<a target="_blank" href="%s" class="card-link">%s</a>', $link, $link);

        // Create the ticket message data
        $ticketMessageData = [
            'customer_name' => $customername,
            'product_name' => $productName,
            'ordernumber' => $ordernumber,
            'articleordernumber' => $product ? $product->getProductNumber() : '',
            'rma_id' => $rmaId,
            'deeplink' => $link,
            'supplier_address' => $returnAddress,
            'status_name' => $status ? $status->getNameExt() : ''
        ];

        // Generate the ticket creation message
        $message = $this->getTicketCreationMessage($context->getContext(), $ticketMessageData);

        $freetextFiles = [];
        $freetextFormValues = [];
        if ($case && $case->getFreetext()) {
            foreach ($case->getFreetext() as $key => $value) {
                $value['name'] = Utility::convertNameToFieldName($value['name']);
                if ($value['dependOnAmount']) {
                    for ($i = 0; $i < $amount; $i++) {
                        $freetextFieldName = sprintf('freetext_%s_%d', $value['name'], $i);
                        $freetextFieldLabel = Utility::cleanString(sprintf('%s %d', $value['name'], $i + 1));
                        $value['label'] = $freetextFieldLabel;

                        // Process the freetext form values
                        if ($request->get($freetextFieldName)) {
                            $fieldValue = $request->get($freetextFieldName);
                            if ($value['type'] === 'date') {
                                $fieldValue = Utility::convertDateToTimezone($fieldValue, Utility::DATE_FORMAT);
                            }
                            if ($value['type'] === 'checkbox') {
                                $fieldValue = $fieldValue === 'on';
                            }
                            $value['value'] = $fieldValue;
                            $freetextFormValues[] = $value;
                        }

                        // Process the freetext file uploads
                        $file = sprintf('file_%s_%d', $value['name'], $i);
                        if ($request->files->get($file)) {
                            $file = $request->files->get($file);
                            if ($file) {
                                try {
                                    $media = $this->fileUploader->uploadFile($file, $context->getContext());
                                } catch (RuntimeException $e) {
                                    return $this->showError(ErrorConstants::FILE_SIZE);
                                } catch (\Exception $e) {
                                    return $this->showError(ErrorConstants::FILE_UPLOAD);
                                }
                                $freetextFiles[] = [
                                    'id' => Uuid::randomHex(),
                                    'mediaId' => $media['mediaId'],
                                    'name' => $media['name']
                                ];
                            }
                        }
                    }
                } else {
                    $freetextFieldName = sprintf('freetext_%s_%d', $value['name'], 0);
                    $freetextFieldLabel = Utility::cleanString($value['name']);
                    $value['label'] = $freetextFieldLabel;

                    // Process the freetext form values
                    if ($request->get($freetextFieldName)) {
                        $fieldValue = $request->get($freetextFieldName);
                        if ($value['type'] === 'date') {
                            $fieldValue = Utility::convertDateToTimezone($fieldValue, Utility::DATE_FORMAT);
                        }
                        if ($value['type'] === 'checkbox') {
                            $fieldValue = $fieldValue === 'on';
                        }
                        $value['value'] = $fieldValue;
                        $freetextFormValues[] = $value;
                    }

                    // Process the freetext file uploads
                    $file = sprintf('file_%s_%d', $value['name'], 0);
                    if ($request->files->get($file)) {
                        $file = $request->files->get($file);
                        if ($file) {
                            try {
                                $media = $this->fileUploader->uploadFile($file, $context->getContext());
                            } catch (RuntimeException $e) {
                                return $this->showError(ErrorConstants::FILE_SIZE);
                            } catch (\Exception $e) {
                                return $this->showError(ErrorConstants::FILE_UPLOAD);
                            }
                            $freetextFiles[] = [
                                'id' => Uuid::randomHex(),
                                'mediaId' => $media['mediaId'],
                                'name' => $media['name']
                            ];
                        }
                    }
                }
            }
        }

        $ticketId = Uuid::randomHex();
        $ticketData = [
            'id' => $ticketId,
            'caseId' => $caseId,
            'customerId' => $customerId,
            'orderId' => $orderId,
            'productId' => $product ? $product->getId() : null,
            'productName' => $productName,
            'statusId' => $status->getId(),
            'amount' => $amount,
            'supplierId' => $supplierId,
            'files' => $freetextFiles,
            'link' => $link,
            'customerEmail' => $customerEmail,
            'badges' => $product ? $this->getBadgeText($product->getId(), $context->getContext()) : '',
            'hash' => $hash,
            'deliveryAddress' => $returnAddress,
            'rmaNumber' => $rmaId,
            'ticketSerialNumber' => (int)explode('-', $rmaId)[1]
        ];

        // Process the freetext content and product serial numbers
        $freetextContent = $freetextFormValues;
        if ($freetextContent && \is_array($freetextContent)) {
            $serialNumbersContent = \array_filter($freetextContent, static function ($tc) {
                return $tc['type'] === 'serial' && !empty($tc['value']);
            });
            $ticketData['productSerialNumbers'] = \array_column($serialNumbersContent, 'value');
        }
        $ticketData['ticketContent'] = $freetextContent;

        // Upsert the ticket data
        $this->upsertTicket($ticketData, $ctx);

        // Prepare history data
        $historyData = [
            'id' => Uuid::randomHex(),
            'ticketId' => $ticketId,
            'statusId' => $status->getId(),
            'read' => true,
            'sender' => Utility::SENDER_KLARSICHT,
            'type' => Utility::TYPE_EXTERNAL,
            'message' => $message
        ];

        // Add the history entry
        $this->addHistory($historyData, $ctx);

        // Get the ticket by RMA number
        $ticket = $this->getTicketByRmaNumber($ctx, $rmaId);
        $info = $this->getTicketInfo($ticket, $context->getContext());

        // Send the internal new ticket email
        $mailTemplate = $this->getMailTemplate($context->getContext(), 'kit.rma.internal.new');
        if ($mailTemplate !== null) {
            $emailData = [
                'rma' => $info,
                'text' => $message
            ];
            $this->sendMail($context->getContext(), $mailTemplate, $emailData);
        }

        $data['ticketNotificationText'] = $message;

        return $this->renderStorefront(
            '@KitRma/rma/success.html.twig',
            [
                'kitRma' => $data,
                'page' => $this->getPageData($request, $context)
            ]
        );
    }


    /**
     * @Route("/rma/ticket/comment", name="frontend.rma.ticket.comment", options={"seo"="false"}, methods={"POST"})
     */
    public function addComment(Request $request, SalesChannelContext $context): Response
    {
        $mediaIds = [];
        $attachments = [];

        // Get the RMA number from the request
        $rmaNumber = $request->get('id');

        // Retrieve the ticket associated with the RMA number
        $ticket = $this->getTicketByRmaNumber($context->getContext(), $rmaNumber);

        // If the ticket doesn't exist, show an error
        if (!$ticket) {
            return $this->showError(ErrorConstants::TICKET_NOT_FOUND);
        }

        // Get the hash from the request
        $hash = $request->get('hash');

        // Verify the hash against the ticket's hash
        if ($hash !== $ticket->getHash()) {
            return $this->showError(ErrorConstants::TICKET_ACCESS_DENIED);
        }

        // Retrieve the uploaded files
        $files = $request->files->all();

        // Process each file
        foreach ($files['files'] as $file) {
            if ($file) {
                try {
                    // Upload the file and store the resulting media information
                    $attachments[] = $media = $this->fileUploader->uploadFile($file, $context->getContext());
                    $mediaIds[] = $media['mediaId'];
                } catch (Exception $e) {
                    // If an exception occurs during file upload, redirect with an error
                    return $this->redirect($ticket->getLink() . '&error=1');
                }
            }
        }

        // Get the new status ID for customer response
        $newStatusId = $this->getConfig($context->getSalesChannel()->getId(), 'customerResponseStatus');

        // Get the new status based on the status ID
        $newStatus = $this->getStatusById($context->getContext(), $newStatusId);

        // Get the previous status of the ticket
        $previousStatus = $ticket->getStatus();

        // Get the comment from the request and convert newlines to <br> tags
        $comment = nl2br($request->get('answer'));

        // Create a history entry for the external communication from the customer
        $historyData = [
            'id' => Uuid::randomHex(),
            'ticketId' => $ticket->getId(),
            'statusId' => $newStatus->getId(),
            'read' => false,
            'sender' => Utility::SENDER_CUSTOMER,
            'type' => Utility::TYPE_EXTERNAL,
            'attachment' => $attachments,
            'message' => $comment
        ];
        $this->addHistory($historyData, $context->getContext());

        // If the previous status and the new status are different, create an internal update history entry
        if ($previousStatus && $previousStatus->getId() !== $newStatus->getId()) {
            $historyData = [
                'id' => Uuid::randomHex(),
                'ticketId' => $ticket->getId(),
                'statusId' => $newStatus->getId(),
                'read' => false,
                'sender' => Utility::SENDER_KLARSICHT,
                'type' => Utility::TYPE_INTERNAL,
                'message' => sprintf(
                    'Das Ticket ist vom Status "%s" auf den Status "%s" gewechselt',
                    $previousStatus->getName(),
                    $newStatus->getName()
                )
            ];
            $ticket = $this->updateTicketStatus(
                [
                    'id' => $ticket->getId(),
                    'statusId' => $newStatus->getId(),
                    'rmaNumber' => $ticket->getRmaNumber()
                ],
                $context->getContext()
            );
            $this->addHistory($historyData, $context->getContext());
        }

        // Get the ticket information
        $info = $this->getTicketInfo($ticket, $context->getContext());

        // Prepare email data
        $emailData = [
            'rma' => $info,
            'text' => $comment,
            'mediaIds' => $mediaIds,
            'sender_name' => empty($info['customer_name']) ? 'KLARSICHT IT' : $info['customer_name'],
        ];

        // Get the email template
        $mailTemplate = $this->getMailTemplate($context->getContext(), 'kit.rma.internal.answer');

        // If the email template exists, send the email
        if ($mailTemplate !== null) {
            $this->sendMail($context->getContext(), $mailTemplate, $emailData);
        }

        // Redirect the user to the ticket page
        return $this->redirect($info['link']);
    }


    /**
     * @Route("/rma/ticket/view", name="frontend.rma.ticket.view", options={"seo"="false"}, methods={"GET", "POST"})
     */
    public function ticketView(Request $request, SalesChannelContext $context): Response
    {
        // Check if the plugin is active
        if (!$this->getConfig($context->getSalesChannel()->getId(), 'active')) {
            throw new RouteNotFoundException();
        }

        // Check if the ticket ID is provided
        if (!$request->get('id')) {
            return $this->showError(ErrorConstants::ORDER_NOT_FOUND);
        }

        // Retrieve the ticket based on the provided ID
        $id = $request->get('id');
        $ticket = $this->getTicketByRmaNumber($context->getContext(), $id);

        // If the ticket doesn't exist, show an error
        if (!$ticket) {
            return $this->showError(ErrorConstants::ORDER_NOT_FOUND);
        }

        // Check the hash against the ticket's hash
        $hash = $request->get('hash');
        if ($hash !== $ticket->getHash()) {
            return $this->showError(ErrorConstants::TICKET_ACCESS_DENIED);
        }

        // Get the ticket information
        $info = $this->getTicketInfo($ticket, $context->getContext());

        // Render the ticket view template
        return $this->renderStorefront(
            '@KitRma/rma/ticket.html.twig',
            [
                'ticket' => $info,
                'logged' => (bool)$context->getCustomer(),
                'page' => $this->getPageData($request, $context)
            ]
        );
    }

    /**
     * @Route("/rma/ticket/pdf/{rmaNumber}", name="frontend.rma.ticket.pdf", options={"seo"="false"}, methods={"GET"})
     */
    public function previewPdf(Request $request, SalesChannelContext $context, string $rmaNumber): Response
    {
        // Check if the customer is logged in
        $customer = $context->getCustomer();
        if (!$customer) {
            throw new CustomerNotLoggedInException();
        }

        // Retrieve the ticket based on the provided RMA number
        $ticket = $this->getTicketByRmaNumber($context->getContext(), $rmaNumber);

        // If the ticket doesn't exist, show an error
        if (!$ticket) {
            return $this->showError(ErrorConstants::TICKET_NOT_FOUND);
        }

        // Check if the customer has access to the ticket
        if ($customer->getCustomerNumber() !== $ticket->getCustomer()->getCustomerNumber()) {
            return $this->showError(ErrorConstants::TICKET_ACCESS_DENIED);
        }

        // Generate the PDF for the ticket
        $pdf = $this->generatePdfForTicket($context->getContext(), $ticket, true);

        // If the PDF is generated successfully, create a response with the PDF content
        if ($pdf) {
            $response = new Response($pdf);
            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $rmaNumber,
                preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $rmaNumber)
            );

            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        }
    }

}
