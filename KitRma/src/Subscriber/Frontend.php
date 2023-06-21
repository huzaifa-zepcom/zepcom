<?php declare(strict_types=1);

namespace KitRma\Subscriber;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class Frontend implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $configService;

    protected $cconnection;

    public function __construct(
        SystemConfigService $configService,
        Connection $cconnection
    ) {
        $this->configService = $configService->getDomain('KitRma');
        $this->cconnection = $cconnection;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 32]],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        // Get the domain from the configuration
        $domain = @$this->configService['KitRma.config.domain'];

        // Get the current URL
        $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        // Create the old URL to redirect to
        $oldurl = 'https://' . $domain . '.klarsicht-it.de';

        // Check if the current URL contains '/Rma'
        if (strpos($actual_link, '/Rma') !== false) {
            // Extract the path after '/Rma'
            $breakr_url = explode('/Rma', $actual_link);

            // Append the extracted path to the old URL
            $oldurl .= '/Rma' . $breakr_url[1];

            // Perform the redirect
            header("Location: $oldurl");
            die;
        }
    }

}
