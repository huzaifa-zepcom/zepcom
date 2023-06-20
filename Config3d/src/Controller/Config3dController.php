<?php

namespace Config3d\Controller;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoader;
use Shopware\Storefront\Page\Product\ProductPageLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class Config3dController extends StorefrontController
{
    protected ProductPageLoader $productPageLoader;
    private CartService $cartService;

    public function __construct(
        ProductPageLoader $productPageLoader,
        CartService $cartService
    ) {
        $this->productPageLoader = $productPageLoader;
        $this->cartService = $cartService;
    }

    /**
     * @Route("/plugin3d", name="frontend.plugin3d.load", methods={"GET"}, defaults={"XmlHttpRequest"=true, "_routeScope"={"storefront"}})
     */
    public function loadConfigurator(Request $request, SalesChannelContext $context): Response
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);
        $lineItemId = $request->get('lineItemId');
        $productId = $request->get('productId');
        if ($lineItemId) {
            $lineItem = $cart->getLineItems()->get($lineItemId);
            $productId = $lineItem ? $lineItem->getReferencedId() : $productId;
        }

        if (!$productId) {
            throw new ProductNotFoundException($productId);
        }

        $request->attributes->set('productId', $productId);
        $page = $this->productPageLoader->load($request, $context);

        $customFields = $page->getProduct()->getCustomFields();
        $configured = $customFields['customization_config_url'] ?? null;
        if (!$configured) {
            throw new RouteNotFoundException();
        }

        return $this->renderStorefront('@Config3d/storefront/page/configurator/index.html.twig', [
            'page' => $page,
            'lineItem' => $lineItem ?? null
        ]);
    }

    /**
     * @Route("/plugin3d/edit", name="frontend.plugin3d.edit", methods={"POST"}, defaults={"XmlHttpRequest"=true, "_routeScope"={"storefront"}})
     */
    public function editConfigurator(Request $request, SalesChannelContext $context): Response
    {
        $lineItems = $request->get('lineItems');
        $first = reset($lineItems);

        $cart = $this->cartService->getCart($context->getToken(), $context);
        $lineItemId = $request->get('config3d-edit');
        $configValue = $request->get('plugin3-config');

        $lineItem = $cart->getLineItems()->get($lineItemId);
        $lineItem->setPayloadValue('plugin3d_config', $configValue);
        $lineItem->setQuantity($first['quantity']);

        $request->getSession()->set($lineItem->getId(), $configValue);

        return $this->createActionResponse($request);
    }
}