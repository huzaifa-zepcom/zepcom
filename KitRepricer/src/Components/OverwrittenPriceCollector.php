<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate\Components;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OverwrittenPriceCollector implements CartDataCollectorInterface, CartProcessorInterface
{
    protected EntityRepositoryInterface $productRepository;

    protected QuantityPriceCalculator $calculator;

    protected KitAutoPriceService $autoPriceService;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        QuantityPriceCalculator $calculator,
        KitAutoPriceService $autoPriceService
    ) {
        $this->productRepository = $productRepository;
        $this->calculator = $calculator;
        $this->autoPriceService = $autoPriceService;
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $products = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);
        if (\count($products) === 0) {
            return;
        }

        $filtered = $this->filterAlreadyFetchedPrices($products, $data);

        if (empty($filtered)) {
            return;
        }

        foreach ($filtered as $product) {
            $key = $this->buildKey($product->getReferencedId());
            if (!$product->getPayloadValue('pvgPrice')) {
                continue;
            }

            $data->set($key, $product);
        }
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $lineItems = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            $key = $this->buildKey($lineItem->getReferencedId());

            if (!$data->has($key) || $data->get($key) === null) {
                continue;
            }

            $customerGross = $context->getCurrentCustomerGroup()->getDisplayGross();

            $definition = new QuantityPriceDefinition(
                (float)($customerGross ? $lineItem->getPayloadValue('pvgPrice') : $lineItem->getPayloadValue('pvgPriceNet')),
                $lineItem->getPrice()->getTaxRules(),
                $lineItem->getPrice()->getQuantity()
            );

            $calculated = $this->calculator->calculate($definition, $context);

            $lineItem->setPrice($calculated);
            $lineItem->setPriceDefinition($definition);
        }
    }

    private function filterAlreadyFetchedPrices(LineItemCollection $products, CartDataCollection $data): array
    {
        $filtered = [];

        foreach ($products as $product) {
            $id = $product->getReferencedId();
            $key = $this->buildKey($id);

            if ($data->has($key)) {
                continue;
            }

            $filtered[] = $product;
        }

        return $filtered;
    }

    private function buildKey(string $id): string
    {
        return 'kit-repricer-' . $id;
    }
}
