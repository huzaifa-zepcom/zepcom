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

        $domain = @$this->configService['KitRma.config.domain'];

        $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $oldurl = 'https://' . $domain . '.klarsicht-it.de';
        $redirect = 0;
        foreach ($request->attributes as $k => $a) {
            if ($k == "resolved-uri") {
                if ($a == '/Rma') {
                    $breakr_url = explode('/Rma', $actual_link);
                    $oldurl .= '/Rma' . $breakr_url[1];
                    header("Location: $oldurl");
                    die;
                }
            }
        }
    }
}
