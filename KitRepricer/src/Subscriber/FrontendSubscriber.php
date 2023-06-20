<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate\Subscriber;

use Exception;
use KitAutoPriceUpdate\Components\KitAutoPriceService;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Shopware\Core\Content\Product\Events\ProductDetailRouteCacheKeyEvent;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FrontendSubscriber implements EventSubscriberInterface
{
    /**
     * @var AbsolutePriceCalculator
     */
    private $priceCalculator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var KitAutoPriceService
     */
    private $autoPriceService;

    public function __construct(
        RequestStack $requestStack,
        AbsolutePriceCalculator $priceCalculator,
        KitAutoPriceService $autoPriceService
    ) {
        $this->priceCalculator = $priceCalculator;
        $this->requestStack = $requestStack;
        $this->autoPriceService = $autoPriceService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.product.loaded' => 'onProduct',
            ProductPageLoadedEvent::class => 'onDetail',
            CustomerLogoutEvent::class => 'unsetToken',
            ProductDetailRouteCacheKeyEvent::class => 'routeCache',
            BeforeLineItemAddedEvent::class => 'onLineItemAdded'
        ];
    }

    public function onProduct(SalesChannelEntityLoadedEvent $event): void
    {
        $salesContext = $event->getSalesChannelContext();
        $customerGross = $salesContext->getCurrentCustomerGroup()->getDisplayGross();
        $isCustomerPVG = $this->isCustomerPVG($event);
        if (!$isCustomerPVG) {
            return;
        }

        /** @var SalesChannelProductEntity $productEntity */
        foreach ($event->getEntities() as $productEntity) {
            if (!$productEntity->getPrice()) {
                continue;
            }

            $calculatedPrice = null;
            $price = $productEntity->getPrice()->first();
            $pvgPrice = KitAutoPriceService::getPvgPriceFromProduct($productEntity);
            if ($price && $pvgPrice) {
                $grossPriceData = $this->autoPriceService->calculateGrossPriceBasedOnCountryTax(
                    $productEntity,
                    $salesContext
                );
                $gross = $grossPriceData['gross'];
                $countryTax = $grossPriceData['countryTax'];

                $net = $gross / $countryTax;
                $price->setGross($gross);
                $price->setNet($net);

                $priceCollection = new PriceCollection([$price]);
                $calculatedPrice = $this->priceCalculator->calculate(
                    ($customerGross ? $gross : $net),
                    $productEntity->getCalculatedPrices(),
                    $salesContext
                );
                $productEntity->setCalculatedPrice($calculatedPrice);
                $productEntity->setPrice($priceCollection);
            }
        }
    }

    /**
     * Route cache method updates the cache if HTTP_CACHE is enabled, so the price shown is not cached on the page.
     *
     * @param ProductDetailRouteCacheKeyEvent $event
     *
     * @return void
     */
    public function routeCache(ProductDetailRouteCacheKeyEvent $event): void
    {
        $request = $event->getRequest();
        $parts = $event->getParts();
        $token = $request->get(KitAutoPriceService::TOKEN, $request->get('repricer'));
        if ($token) {
            $parts[] = $token;
        }

        $event->setParts($parts);
    }

    private function isCustomerPVG(ShopwareSalesChannelEvent $event): bool
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if ($customer) {
            $customFields = $customer->getCustomFields();

            return isset($customFields['kit_customer_pvg']) && $customFields['kit_customer_pvg'];
        }

        return false;
    }

    public function onDetail(ProductPageLoadedEvent $event): void
    {
        $request = $event->getRequest();
        $pgToken = $request->query->get(KitAutoPriceService::TOKEN);
        $cpToken = $request->query->get('cp');
        $product = $event->getPage()->getProduct();
        $id = $product->getId();
        $isCustomerPVG = $this->isCustomerPVG($event);
        $repricer = $request->get('repricer');
        $grossPriceData = [];

        try {
            // Check for PVG price
            if ($pgToken) {
                if (!$repricer && !$isCustomerPVG && !$this->hasValidToken($id, $pgToken)) {
                    return;
                }

                $grossPriceData = $this->autoPriceService->calculateGrossPriceBasedOnCountryTax(
                    $product,
                    $event->getSalesChannelContext()
                );
            }

            // Check for CP price
            if ($cpToken) {
                if (!$this->hasValidToken($id, $cpToken)) {
                    return;
                }

                $grossPriceData = $this->autoPriceService->calculateGrossPriceBasedOnCountryTax(
                    $product,
                    $event->getSalesChannelContext(),
                    'cp'
                );
            }
        } catch (Exception $e) {
            // ignore exception as we are returning below. It will fallback to default shopware price.
        }

        if (!$grossPriceData) {
            return;
        }

        $gross = $grossPriceData['gross'];
        $countryTax = $grossPriceData['countryTax'];

        // Debug repricer tag `?repricer=1` if used, then it shows these values on detail page.
        if ($repricer) {
            $data = [
                'repricer' => $gross,
                'repricerNet' => $gross / $countryTax,
                'repricerTax' => $countryTax * 100 - 100
            ];
        } else {
            // We use the same variable for PVG and CP, and differentiate the type using the token below:
            // pgToken for PVG and cpToken for Campus product.
            // This token is used to calcualte the price in cart.
            $data = [
                'pvgPrice' => $gross,
                'pvgPriceNet' => $gross / $countryTax,
                'calculatedTax' => $countryTax * 100 - 100
            ];

            if($pgToken) {
                $data['pgToken'] = $pgToken;
                $this->setToken($id, $pgToken);
            } else if ($cpToken) {
                $data['cpToken'] = $cpToken;
                $this->setToken($id, $cpToken, 'cp');
            }
        }

        $event->getPage()->assign($data);
    }

    public function onLineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        // The token is passed in the add to cart form so we can show the correct price.
        $lineItem = $event->getLineItem();
        if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request) {
                return;
            }

            $params = $request->request->all();
            $productEntity = $this->autoPriceService->getProductFromLineItem($lineItem, $event->getContext());
            $id = $lineItem->getReferencedId();

            $cpToken = $params['cptoken'] ?? null;
            if ($cpToken) {
                $token = $cpToken;
            } else {
                $token = $params['pgtoken'] ?? null;
            }

            if (!$this->hasValidToken($id, $token)) {
                return;
            }

            if ($cpToken) {
                $lineItem->setPayloadValue('hasCampusPrice', true);
                $grossPriceData = $this->autoPriceService->calculateGrossPriceBasedOnCountryTax(
                    $productEntity,
                    $event->getSalesChannelContext(),
                    'cp'
                );
            } else {
                $lineItem->setPayloadValue('hasPvgPrice', true);
                $grossPriceData = $this->autoPriceService->calculateGrossPriceBasedOnCountryTax(
                    $productEntity,
                    $event->getSalesChannelContext()
                );
            }

            $gross = $grossPriceData['gross'];
            $countryTax = $grossPriceData['countryTax'];
            $net = $gross / $countryTax;

            $lineItem->setPayloadValue('pvgPrice', $gross);
            $lineItem->setPayloadValue('pvgPriceNet', $net);
        }
    }

    private function hasValidToken($id, $token): bool
    {
        $productToken = md5($id);

        return $productToken === $token;
    }

    public function setToken($id, $token, $type = KitAutoPriceService::TOKEN): void
    {
        $session = $this->getSession();
        $tokens = $session->get($type);
        $tokens[$id] = $token;
        $session->set($type, $tokens);
    }

    public function unsetToken(CustomerLogoutEvent $event): void
    {
        $session = $this->getSession();
        $session->remove(KitAutoPriceService::TOKEN);
        $session->save();
    }

    protected function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
