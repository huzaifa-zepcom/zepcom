<?php

namespace Config3d\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class Config3dProcessor implements CartProcessorInterface
{
    private Session $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        // Here we check if the item has a changed config, if so, we set the new config in the payload
        foreach ($toCalculate->getLineItems()->getFlat() as $lineItem) {
            if ($this->session->has($lineItem->getId())) {
                $config3d = $this->session->get($lineItem->getId());
                if ($config3d) {
                    $lineItem->setPayloadValue('plugin3d_config', $config3d);
                }
            }
        }
    }
}