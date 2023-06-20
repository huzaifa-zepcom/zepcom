<?php

declare(strict_types=1);

namespace KitInvoiceViewer\Controller;

use Exception;
use KitInvoiceViewer\Service\KitInvoiceService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Throwable;

use function file_get_contents;
use function preg_replace;

/**
 * @RouteScope(scopes={"storefront"})
 */
class KitInvoiceController extends StorefrontController
{
    /**
     * @var KitInvoiceService
     */
    private $service;

    public function __construct(KitInvoiceService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("/kit/invoice/{hash}", name="frontend.kit.invoice", options={"seo"="false"}, methods={"GET"})
     */
    public function invoice(string $hash): Response
    {
        $file = $this->service->getInvoiceFromHash($hash);
        try {
            if (isset($file['file_name'])) {
                $response = new Response(file_get_contents($file['file_name']));

                $disposition = HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_INLINE,
                    $hash,
                    preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $hash)
                );

                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', $disposition);

                return $response;
            }
        } catch (Throwable $e) {
        }

        $this->addFlash('danger', 'No invoice found');

        return $this->redirectToRoute('frontend.account.home.page');
    }
}
