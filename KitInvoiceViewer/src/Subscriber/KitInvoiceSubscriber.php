<?php

declare(strict_types=1);

namespace KitInvoiceViewer\Subscriber;

use KitInvoiceViewer\Service\KitInvoiceService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Storefront\Page\Account\Order\AccountOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\Overview\AccountOverviewPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KitInvoiceSubscriber implements EventSubscriberInterface
{
    /**
     * @var KitInvoiceService
     */
    private $service;

    public function __construct(KitInvoiceService $service)
    {
        $this->service = $service;
    }

    public static function getSubscribedEvents()
    {
        return [
            AccountOrderPageLoadedEvent::class => 'orderPageLoaded',
            AccountOverviewPageLoadedEvent::class => 'overviewPageLoaded'
        ];
    }

    public function orderPageLoaded(AccountOrderPageLoadedEvent $event): void
    {
        $orders = $event->getPage()->getOrders();
        if ($orders && $orders->getElements()) {
            /** @var OrderEntity $order */
            foreach ($orders->getElements() as $order) {
                $this->checkInvoice($order);
            }
        }
    }

    public function overviewPageLoaded(AccountOverviewPageLoadedEvent $event): void
    {
        $order = $event->getPage()->getNewestOrder();
        if ($order) {
            $this->checkInvoice($order);
        }
    }

    private function checkInvoice(OrderEntity $order): void
    {
        $invoices = $this->service->getInvoicesForOrder($order->getOrderNumber());
        if ($invoices) {
            $order->assign(['invoices' => $invoices]);
        }
    }
}
