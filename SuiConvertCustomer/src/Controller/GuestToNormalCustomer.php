<?php

declare(strict_types=1);

namespace SuiConvertCustomer\Controller;

use Exception;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class GuestToNormalCustomer extends StorefrontController
{
    protected GenericPageLoader $genericPageLoader;

    protected SystemConfigService $configService;

    public function __construct(
        GenericPageLoader $genericPageLoader,
        SystemConfigService $configService
    ) {
        $this->genericPageLoader = $genericPageLoader;
        $this->configService = $configService;
    }

    /**
     * @Route("/register-guest-customer/{id}/{deepLinkCode}", name="frontend.sui.guest.register", options={"seo"="false"},
     *     methods={"GET", "POST"})
     */
    public function indexRoute(string $id, string $deepLinkCode, Request $request, SalesChannelContext $context): Response
    {
        $page = $this->genericPageLoader->load($request, $context);
        $meta = $page->getMetaInformation();
        if ($meta) {
            $meta->setRobots('nofollow,noindex');
        }

        $template = '@SuiConvertCustomer/storefront/page/account/gtnc/index.html.twig';

        $data = [
            'success' => false,
            'key' => 'gtnc.noCustomer',
            'page' => $page
        ];

        $active = $this->configService->get('SuiConvertCustomer.config.active', $context->getSalesChannelId());
        if (!$active) {
            return $this->renderStorefront($template, $data);
        }

        if ($deepLinkCode && $id && Uuid::isValid($id)) {
            $customer = $this->getCustomerFromOrderHash($id, $deepLinkCode, $context->getContext());

            if ($customer) {
                $registered = $this->checkIfEmailRegistered($customer, $context);

                if (!$registered && $customer->getEmail() && $customer->getGuest()) {
                    $data = [
                        'hash' => $deepLinkCode,
                        'email' => $customer->getEmail(),
                        'success' => true,
                        'customerId' => $id
                    ];
                } else {
                    $data = [
                        'key' => 'gtnc.alreadyCustomer',
                        'email' => $customer->getEmail(),
                        'success' => false
                    ];
                }
            }
        }

        try {
            $customerPasswordRequest = $request->get('guestPass');
            if ($customerPasswordRequest) {
                $modifiedCustomer = [
                    'id' => $id,
                    'password' => $request->get('password'),
                    'guest' => false
                ];

                $customerRepository = $this->container->get('customer.repository');
                $customerRepository->upsert([$modifiedCustomer], $context->getContext());

                $data = ['success' => true];
            }
        } catch (Exception $e) {
            $data = [
                'message' => $e->getMessage(),
                'success' => false,
                'key' => 'gtnc.unknown'
            ];
        }

        $data['page'] = $page;
        $response = $this->renderStorefront($template, $data);
        $response->headers->set('X-Robots-Tag', 'noindex,nofollow');

        return $response;
    }

    public function getCustomerFromOrderHash(string $customerId, string $hash, Context $context): ?CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('deepLinkCode', $hash));
        $criteria->addAssociation('orderCustomer');
        $orderRepo = $this->container->get('order.repository');
        $order = $orderRepo->search($criteria, $context)->first();
        if ($order) {
            $criteria = new Criteria([$customerId]);
            $customerRepository = $this->container->get('customer.repository');

            return $customerRepository->search($criteria, $context)->first();
        }

        return null;
    }

    private function checkIfEmailRegistered(CustomerEntity $customer, SalesChannelContext $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $customer->getEmail()));
        $criteria->addFilter(new EqualsFilter('guest', false));

        $customerRepository = $this->container->get('customer.repository');

        $total = $customerRepository->searchIds($criteria, $context->getContext())->getTotal();

        return $total > 0;
    }
}
