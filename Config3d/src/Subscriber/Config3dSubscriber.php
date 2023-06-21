<?php declare(strict_types=1);

namespace Config3d\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class Config3dSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private EntityRepositoryInterface $plugin3dRepository;

    public function __construct(
        RequestStack $requestStack,
        EntityRepositoryInterface $plugin3dRepository
    ) {
        $this->requestStack = $requestStack;
        $this->plugin3dRepository = $plugin3dRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'lineItemAdded',
            CheckoutOrderPlacedEvent::class => 'orderPlaced'
        ];
    }

    /**
     * @param BeforeLineItemAddedEvent $event
     */
    public function lineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $isEdit = (bool)$request->request->get('config3d-edit', false);
            $configValue = $request->request->get('plugin3-config');
            if(!$configValue) {
                return;
            }

            $lineItem = $event->getLineItem();
            if ($lineItem->getType() !== 'product') {
                return;
            }

            // We create a new lineItem ID here because we want to be able to order different configurations
            // of the same article. By default, it would increase the quantity instead of creating a new line item
            if (!$isEdit) {
                $lineItem->setId(Uuid::randomHex());
            }

            if ($configValue) {
                $lineItem->setPayloadValue('plugin3d_config', $configValue);
                $request->getSession()->set($lineItem->getId(), $configValue);
            }
        }
    }

    public function orderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();
        $lineItems = $order->getLineItems();
        $k = 1;
        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== 'product' && !$lineItem->getProductId()) {
                continue;
            }

            $payload = $lineItem->getPayload();

            if (!isset($payload['plugin3d_config']) || empty($payload['plugin3d_config'])) {
                continue;
            }

            // We save the info in the custom table which will be synced via command
            $configData = [
                'customerOrderReference' => sprintf('%s_%s', $order->getOrderNumber(), $k++),
                'customerPrice' => $lineItem->getTotalPrice(),
                'currency' => $order->getCurrency()->getShortName(),
                'model' => $payload['plugin3d_config']
            ];

            $data = [
                'id' => $lineItem->getId(),
                'lineItemId' => $lineItem->getId(),
                'productId' => $lineItem->getProductId(),
                'orderId' => $order->getId(),
                'configData' => $configData,
                'tryAttemptNumber' => 0,
                'nextAttemptAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'responseStatus' => null,
                'responseData' => null,
                'failed' => null
            ];

            $this->plugin3dRepository->upsert([$data], $event->getContext());
        }
    }
}