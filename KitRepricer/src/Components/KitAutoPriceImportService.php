<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate\Components;

use DateTime;
use Doctrine\DBAL\Connection;
use Exception;
use KitAutoPriceUpdate\Helper\Utility;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function date;
use function getenv;
use function sprintf;

class KitAutoPriceImportService
{
    /**
     * @var SystemConfigService
     */
    private $configService;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var array|false|string
     */
    private $environment;

    public function __construct(
        SystemConfigService $configService,
        Connection $connection,
        ContainerInterface $container
    ) {
        $this->configService = $configService;
        $this->productRepository = $container->get('product.repository');
        $this->connection = $connection;
        $this->logger = new Logger('kit-repricer-import');
        $this->logger->pushHandler(
            new StreamHandler(
                sprintf('%s/../Logs/%s.log', __DIR__, date('YmdH')),
                Logger::DEBUG
            )
        );
        $this->environment = getenv('APP_ENV');
    }

    public function execute()
    {
        $this->log('Truncate', 'Deleting old price data ...');
        $this->connection->executeStatement('TRUNCATE TABLE `kit_priceupdate`');
        $filePath = $this->getConfig('importFilePath');

        $payload = [];

        // parse the file and return formatted data as array
        $this->log('Import', 'Importing data from: ' . $filePath);
        $results = Utility::getCsvFormattedData($filePath);

        $this->log('Import', 'Found ' . count($results) . ' records');

        $columns[] = 'id';
        $columns[] = 'product_id';
        $columns[] = 'geizhalsID';
        $columns[] = 'geizhalsArtikelname';
        $columns[] = 'meinPreis';

        // Add columns for price, provider, and lz (repeated 10 times)
        for ($i = 1; $i <= 10; $i++) {
            $columns[] = 'price' . $i;
            $columns[] = 'anbieter' . $i;
            $columns[] = 'lz' . $i;
        }

        $columns[] = 'meineArtikelnummer';
        $columns[] = 'geizhalsArtikelURL';
        $columns[] = 'created_at';

        foreach ($results as $key => $row) {
            try {
                $productId = $this->getProductIdByNumber($row['MeineArtikelnummer']);

                if (!$productId) {
                    continue;
                }

                $temp['id'] = Uuid::fromHexToBytes(md5($row['MeineArtikelnummer']));
                $temp['product_id'] = $productId;
                $row = $temp + $row;

                // Remove unnecessary columns
                unset($row['UpdateDatum'], $row['UpdateUhrzeit']);

                $payload[] = $row;
            } catch (Exception $e) {
                // Handle exception if needed
            }
        }

        $columnValues = implode(',', $columns);
        $placeholder = [];
        $values = [];
        $totalRecords = count($payload);
        $counter = 0;
        $processed = 0;

        foreach ($payload as $key => $row) {
            $insertQuery = [];

            foreach ($row as $column) {
                $insertQuery[] = '?';
                $values[] = $column;
            }

            if (!empty($insertQuery)) {
                $insertQuery[] = '?';
                $placeholder[] = sprintf('(%s)', implode(', ', $insertQuery));
            }

            if ($counter === 0 && !$this->connection->isTransactionActive()) {
                $this->connection->beginTransaction();
            }

            try {
                $values[] = (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
                $params = implode(', ', $placeholder);

                $sql = sprintf(
                    'INSERT INTO kit_priceupdate (%s) VALUES %s',
                    $columnValues,
                    $params
                );

                $stmt = $this->connection->prepare($sql);
                $stmt->execute($values);
                $counter++;

                if ($counter === 500) {
                    $processed += $counter;
                    $this->log('Import', 'Processed ' . ($key + 1) . ' records');
                    $this->connection->commit();
                    $counter = 0;
                }

                $placeholder = [];
                $values = [];
            } catch (Exception $e) {
                echo 'An error occurred. Please contact developer' . PHP_EOL;
                $this->connection->rollBack();
                $this->log('Error', $e->getMessage(), true);
                die;
            }
        }

        if ($this->connection->isTransactionActive()) {
            $processed += $counter;
            $this->log('Import', 'Processed ' . ($processed) . ' records');
            $this->connection->commit();
        }

        $this->log('Import', sprintf('%s out of %s records processed', $processed, $totalRecords));
        echo PHP_EOL;
    }


    private function getConfig(string $config)
    {
        return $this->configService->get(sprintf('KitAutoPriceUpdate.config.%s', $config));
    }

    private function log(string $title, $message, $isError = false): void
    {
        if ($this->environment !== 'dev' && !$isError) {
            return;
        }

        if (is_array($message)) {
            $info = '';
            foreach ($message as $key => $value) {
                $info .= sprintf('%s: %s | ', $key, trim(strip_tags(str_replace('<', ' <', $value))));
            }
            $message = $info;
        }

        $message = sprintf('KitRepricer: %s - | %s', $title, $message);
        $this->logger->debug($message);
        echo $message . PHP_EOL;
    }

    private function getProductIdByNumber(string $productNumber): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        $criteria->setLimit(1);
        $productId = $this->productRepository->searchIds($criteria, Context::createDefaultContext())->firstId();
        if (!$productId) {
            return null;
        }

        return Uuid::fromHexToBytes($productId);
    }
}
