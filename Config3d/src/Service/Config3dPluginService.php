<?php declare(strict_types=1);

namespace Config3d\Service;

use DateInterval;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Config3d\Content\Config3dPluginEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Style\SymfonyStyle;

class Config3dPluginService
{
    private EntityRepositoryInterface $repository;
    private EntityRepositoryInterface $orderLineItemRepository;
    private SystemConfigService $configService;
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepositoryInterface $repository,
        EntityRepositoryInterface $orderLineItemRepository,
        SystemConfigService $configService,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->orderLineItemRepository = $orderLineItemRepository;
        $this->configService = $configService;
        $this->logger = $logger;

        $this->client = new Client([
            'base_uri' => $configService->get('Config3d.config.baseUrl'),
            'timeout' => 20,
        ]);
    }

    public function sync(SymfonyStyle $io): void
    {
        $context = Context::createDefaultContext();
        $records = $this->getRecords($context);

        $io->note('Found ' . $records->count() . ' records to process');

        $updatedRecords = [];
        $successRecords = 0;

        /** @var Config3dPluginEntity $record */
        foreach ($records as $record) {

            try {
                $response = $this->sendRequest($record->getConfigData());
                $content = $response->getBody()->getContents();
                $statusCode = (string)$response->getStatusCode();
                $successRecords++;
            } catch (\Exception $e) {
                $statusCode = (string)$e->getCode();
                $content = $e->getMessage();
            }


            $offset = $record->getTryAttemptNumber() ?? 0;
            $nextTry = $offset === 0 ? 1 : $offset * 2;

            $interval = new DateInterval('PT' . $nextTry . 'H');
            $nextDate = (new \DateTimeImmutable)->add($interval);
            $nextDateAt = $nextDate->format(Defaults::STORAGE_DATE_TIME_FORMAT);

            $failed = null;

            if (strpos($statusCode, '5') === 0 || strpos($statusCode, '4') === 0) {
                if ($nextTry >= 32) {
                    $io->warning('Item failed on the last attempt: ' . $record->getLineItemId());
                    $nextTry = 32;
                    $nextDateAt = null;
                    $failed = true;
                }
            }

            // If status is 200 means we do not need to handle this in the next iteration, so
            // we set the next attempt date to null.
            if (strpos($statusCode, '2') === 0) {
                $nextDateAt = null;
                $failed = false;
                $nextTry = 0;
            }

            $data = [
                'id' => $record->getId(),
                'tryAttemptNumber' => $nextTry,
                'nextAttemptAt' => $nextDateAt,
                'responseStatus' => (int)$statusCode,
                'responseData' => $content,
                'failed' => $failed
            ];

            if ($failed) {
                $this->logger->error('Final attempt failed for item: ' . $record->getLineItemId(), $data);
            }

            $updatedRecords[] = $data;
        }

        if ($updatedRecords) {
            $this->repository->upsert($updatedRecords, $context);
        }

        $io->success($successRecords . '/' . count($updatedRecords) . ' success records');
    }

    private function getRecords(Context $context): \Shopware\Core\Framework\DataAbstractionLayer\EntityCollection
    {
        $today = (new \DateTimeImmutable);

        $critera = new Criteria();
        $critera->addFilter(new EqualsFilter('failed', null));
        $critera->addFilter(new RangeFilter('nextAttemptAt',
            [RangeFilter::LTE => $today->format(Defaults::STORAGE_DATE_TIME_FORMAT)]
        ));

        return $this->repository->search($critera, $context)->getEntities();
    }

    public function sendRequest(array $data): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->post('/api/order', [
                'json' => $data,
                'headers' => [
                    'X-ApiKey' => $this->configService->get('Config3d.config.apiKey')
                ]
            ]
        );

    }

    public function clean(SymfonyStyle $io)
    {
        $io->title('Cleaning old records ...');
        $context = Context::createDefaultContext();
        $xDays = (int)$this->configService->get('Config3d.config.deleteAfterDays');
        $interval = new DateInterval('P' . $xDays . 'D');
        $limitDate = (new \DateTimeImmutable)->sub($interval);

        $io->note('Fetching entries older than ' . $limitDate->format('d.m.Y'));

        $critera = new Criteria();
        $critera->addFilter(new RangeFilter('updatedAt',
            [RangeFilter::LTE => $limitDate->format(Defaults::STORAGE_DATE_TIME_FORMAT)]
        ));

        $deleteIds = [];
        $records = $this->repository->search($critera, $context)->getEntities();

        $io->note('Found ' . $records->count() . ' records');

        /** @var Config3dPluginEntity $record */
        foreach ($records as $record) {

            $critera = new Criteria([$record->getLineItemId()]);

            /** @var OrderLineItemEntity $lineItem */
            $lineItem = $this->orderLineItemRepository->search($critera, $context)->first();
            if ($lineItem) {
                $payload = $lineItem->getPayload();
                unset($payload['plugin3d_config']);

                $this->orderLineItemRepository->update([
                    [
                        'id' => $lineItem->getId(),
                        'productId' => $lineItem->getProductId(),
                        'referencedId' => $lineItem->getReferencedId(),
                        'payload' => $payload
                    ]
                ], $context);

                $deleteIds[] = [
                    'id' => $record->getId()
                ];
            }
        }

        if ($deleteIds) {
            $this->repository->delete($deleteIds, $context);
        }

        $io->success('Removed ' . count($deleteIds) . ' records');
    }
}