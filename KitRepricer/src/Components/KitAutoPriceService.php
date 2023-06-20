<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate\Components;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Exception;
use KitAutoPriceUpdate\Content\Repricer\RepricerEntity;
use KitAutoPriceUpdate\Helper\Utility;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RuntimeException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\Tax\Aggregate\TaxRule\TaxRuleEntity;

use function array_filter;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function getenv;
use function is_array;
use function sprintf;
use function str_replace;
use function strtoupper;

class KitAutoPriceService
{
    public const TOKEN = 'pg';
    public const LIMIT = 500;
    private const FORMAT = 'd.m.Y H:i';
    public const PVG_RULE_ID = 'bcc709ab7d5273aa1fe785073b278a41';

    /** @var EntityRepositoryInterface */
    protected $repricerRepository;

    /** @var Connection */
    private $connection;

    /** @var SystemConfigService */
    private $configService;

    /** @var Logger */
    private $logger;

    /** @var EntityRepositoryInterface */
    private $repository;

    /** @var array */
    private $excludedAffiliates;

    /** @var bool */
    private $testMode = false;

    /** @var string */
    private $environment;

    /*** @var bool */
    private $isInsideBusinessHour;

    /** @var EntityRepositoryInterface */
    private $taxRuleRepository;

    public function __construct(
        Connection $connection,
        SystemConfigService $configService,
        EntityRepositoryInterface $repository,
        EntityRepositoryInterface $repricerRepository,
        EntityRepositoryInterface $taxRuleRepository
    ) {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->logger = new Logger('kit-repricer-rules');
        $this->logger->pushHandler(
            new StreamHandler(
                sprintf('%s/../Logs/%s.log', __DIR__, date('YmdH')),
                Logger::DEBUG
            )
        );
        $this->repository = $repository;
        $this->repricerRepository = $repricerRepository;
        $this->environment = getenv('APP_ENV');
        $this->taxRuleRepository = $taxRuleRepository;
    }

    public function execute($type, $testMode = false, $exceptionIds = []): array
    {
        $this->testMode = $testMode;
        $this->log('', '=========================================================');
        $this->log($type, 'Executing in ' . ($testMode ? 'TEST' : 'LIVE') . ' mode');
        $base = $this->getBaseRule($type);
        $exceptions = $this->getExeptionRules($type);

        if (empty($base) && empty($exceptions)) {
            $this->error($type, 'No rules found');

            return [];
        }

        $businessHourStart = $this->getConfig('businessHourStart');
        $businessHourEnd = $this->getConfig('businessHourEnd');

        if (!$businessHourStart || !$businessHourEnd) {
            $this->error($type, 'Business hours are not defined in config');

            return [];
        }

        $now = new Datetime();
        $begintime = new DateTime($businessHourStart, $this->getTimezone());
        $endtime = new DateTime($businessHourEnd, $this->getTimezone());

        if ($now >= $begintime && $now <= $endtime) {
            $businessHour = 'Inside';
            $this->isInsideBusinessHour = true;
        } else {
            $businessHour = 'Outside';
            $this->isInsideBusinessHour = false;
        }

        $this->log(
            $type,
            sprintf(
                '%s business hours: %s - %s',
                $businessHour,
                $begintime->format('H:i'),
                $endtime->format('H:i')
            )
        );

        $affiliates = $this->getConfig('excludedAffiliates');
        if (trim($affiliates)) {
            $this->excludedAffiliates = explode(',', $affiliates);
        }

        return $this->processRules($type, $base, $exceptions, $exceptionIds);
    }

    private function processRules(string $type, array $base, array $exceptions, $exceptionIds): array
    {
        $this->log($type, 'Processing rule type ... ');
        $context = Context::createDefaultContext();
        $total = $updated = $failed = 0;
        $rulesCount = 0;
        $records = [];
        // Fetch all those rules that are marked as excluded.
        $rules = $this->getExcludedRuleByType($type);

        foreach ($rules as $rule) {
            $criteria = $this->createCriteriaFromRule($rule);
            $values = array_values($this->repricerRepository->searchIds($criteria, $context)->getIds());

            $exceptionIds[] = $values;
        }

        $exceptionIds = array_unique(Utility::flatten($exceptionIds));

        // ====================================== EXCEPTION RULE ========================================== //

        if (!empty($exceptions)) {
            $this->log('Rules', 'Found ' . count($exceptions) . ' exception rule(s)');
            foreach ($exceptions as $key => $rule) {
                $this->log($rule['name'], 'Processing rule ...');
                $minMargin = $rule['minMargin'];

                if ($type === 'sink') {
                    $rule['marginRaw'] = $minMargin;
                    $rule['margin'] = (($minMargin + 100) / 100);
                } elseif ($type === 'raise') {
                    $rule['marginRaw'] = $minMargin;
                    $rule['margin'] = $minMargin;
                }

                try {
                    $this->log($rule['name'], 'Building criteria ...');
                    $criteria = $this->createCriteriaFromRule($rule);
                    if ($type === 'raise') {
                        $criteria->addFilter(new ContainsFilter('anbieter1', 'klarsicht'));
                    }
                    if ($exceptionIds) {
                        $criteria->addFilter(
                            new NotFilter(
                                NotFilter::CONNECTION_AND,
                                [
                                    new EqualsAnyFilter('id', $exceptionIds)
                                ]
                            )
                        );
                    }
                    $ids = $this->repricerRepository->searchIds($criteria, $context);
                    $idTotal = $ids->getTotal();
                    $ids = $ids->getIds(); // array_diff($ids->getIds(), $exceptionIds);
                    $total += $idTotal;
                    $ruleTotal = $idTotal;
                    $this->log($rule['name'], sprintf('Total %s products for rule "%s"', $idTotal, $rule['name']));
                    $offset = 0;
                    if ($ruleTotal) {
                        // Due to large number of records, we process them in chunks
                        while ($offset <= $ruleTotal) {
                            $this->log($rule['name'], sprintf('Processing chunk of %d / %d', $offset, $ruleTotal));
                            $criteria = new Criteria();
                            $criteria->setIds($ids);
                            $criteria->addAssociation('product');
                            $criteria->addAssociation('product.prices');
                            $criteria->setOffset($offset)->setLimit(self::LIMIT);
                            if ($type === 'raise') {
                                $criteria->addFilter(new ContainsFilter('anbieter1', 'klarsicht'));
                            }

                            $repricerProducts = $this->repricerRepository->search($criteria, $context)->getEntities();
                            $found = $repricerProducts->count();
                            $offset += self::LIMIT;
                            $this->log(
                                $rule['name'],
                                sprintf('Found %s products for rule "%s"', $found, $rule['name'])
                            );
                            if (!$found) {
                                continue;
                            }
                            /** @var RepricerEntity $repricer */
                            foreach ($repricerProducts->getElements() as $repricer) {
                                $product = $repricer->getProduct();
                                $this->log($rule['name'], 'Processing ...', $product->getProductNumber());
                                if (\in_array($product->getId(), $exceptionIds, false)) {
                                    $this->log($rule['name'], 'Already processed ...', $product->getProductNumber());
                                    continue;
                                }

                                if ($type === 'sink') {
                                    $result = $this->executeSink($repricer, $product, $rule);
                                    if (!empty($result)) {
                                        $records[] = $result;
                                    }
                                } else {
                                    $supplier = $repricer->getAnbieter1();
                                    if ($supplier) {
                                        if (stripos($supplier, 'klarsicht it') === false) {
                                            $this->log(
                                                $rule['name'],
                                                'KLARSICHT IT was NOT at 1st position',
                                                $product->getProductNumber()
                                            );
                                            continue;
                                        }

                                        $result = $this->executeRaise($repricer, $product, $rule);
                                        if (!empty($result)) {
                                            $records[] = $result;
                                        }
                                    }
                                }
                            }

                            try {
                                $data = $this->generateData($records);
                                $count = count($data);
                                if (!$count) {
                                    continue;
                                }

                                $records = [];
                                if (!$this->testMode) {
                                    $this->log($rule['name'], 'Writing to database for LIVE mode');
                                    $this->repository->update($data, $context);
                                    $updated += $count;
                                    $this->log(
                                        $rule['name'],
                                        sprintf('Updated %d/%d products', $updated, $total)
                                    );
                                } else {
                                    $this->log($rule['name'], 'Skipping writing to database for TEST mode');
                                }
                            } catch (Exception $e) {
                                $this->error(
                                    'Data Error',
                                    sprintf(
                                        'Exception: File: %s - Line: %s - %s',
                                        $e->getFile(),
                                        $e->getLine(),
                                        $e->getMessage()
                                    )
                                );
                            }
                        }
                    } else {
                        $this->log($rule['name'], 'No products found');
                    }
                    $exceptionIds[] = $ids;
                    $exceptionIds = array_unique(Utility::flatten($exceptionIds));
                } catch (Exception $e) {
                    $this->error(
                        $rule['name'],
                        sprintf(
                            'Exception: File: %s - Line: %s - %s',
                            $e->getFile(),
                            $e->getLine(),
                            $e->getMessage()
                        )
                    );
                    continue;
                }
            }

            $logMessage = 'Total ' . sprintf('%s/%s products were updated', $updated, $total);
            $this->error($type, $logMessage);
        }

        // ========================================== BASE RULE ============================================== //

        if ($base) {
            $records = [];
            $updated = $total = $failed = 0;
            $minMargin = $base['minMargin'];
            if ($type === 'sink') {
                $base['marginRaw'] = $minMargin;
                $base['margin'] = (($minMargin + 100) / 100);
            } elseif ($type === 'raise') {
                $base['marginRaw'] = $minMargin;
                $base['margin'] = $minMargin;
            }

            $base['name'] = $type . ' base';

            $criteria = $this->createCriteriaFromRule($base);
            if ($type === 'raise') {
                $criteria->addFilter(new ContainsFilter('anbieter1', 'klarsicht'));
            }
            if ($exceptionIds) {
                $criteria->addFilter(
                    new NotFilter(
                        NotFilter::CONNECTION_AND,
                        [
                            new EqualsAnyFilter('id', $exceptionIds)
                        ]
                    )
                );
            }
            $ids = $this->repricerRepository->searchIds($criteria, $context);
            $idTotal = $ids->getTotal();
            $ids = $ids->getIds(); // array_diff($ids->getIds(), $exceptionIds);
            $total += $idTotal;
            $ruleTotal = $idTotal;
            $this->log($base['name'], sprintf('Total %s products for rule "%s"', $idTotal, $base['name']));
            $offset = 0;
            if ($ruleTotal) {
                while ($offset <= $ruleTotal) {
                    $this->log($base['name'], sprintf('Processing chunk of %d / %d', $offset, $ruleTotal));
                    $criteria = new Criteria();
                    $criteria->setIds($ids);
                    $criteria->addAssociation('product');
                    $criteria->addAssociation('product.prices');
                    $criteria->setOffset($offset)->setLimit(self::LIMIT);
                    $repricerProducts = $this->repricerRepository->search($criteria, $context)->getEntities();
                    $found = $repricerProducts->count();
                    $offset += self::LIMIT;
                    $this->log($base['name'], sprintf('Found %s products for rule "%s"', $found, $base['name']));
                    if (!$found) {
                        continue;
                    }
                    /** @var RepricerEntity $repricer */
                    foreach ($repricerProducts->getElements() as $repricer) {
                        $product = $repricer->getProduct();
                        $this->log($base['name'], 'Processing ...', $product->getProductNumber());
                        if (\in_array($product->getId(), $exceptionIds, false)) {
                            $this->log($base['name'], 'Already processed ...', $product->getProductNumber());
                            continue;
                        }

                        if ($type === 'sink') {
                            $result = $this->executeSink($repricer, $product, $base);
                            if (!empty($result)) {
                                $records[] = $result;
                            }
                        } else {
                            $supplier = $repricer->getAnbieter1();
                            if ($supplier) {
                                if (stripos($supplier, 'klarsicht it') === false) {
                                    $this->log(
                                        $base['name'],
                                        'KLARSICHT IT was NOT at 1st position: ' . $supplier,
                                        $product->getProductNumber()
                                    );
                                    continue;
                                }

                                $result = $this->executeRaise($repricer, $product, $base);
                                if (!empty($result)) {
                                    $records[] = $result;
                                }
                            }
                        }
                    }

                    try {
                        $data = $this->generateData($records);
                        $count = count($data);
                        if (!$count) {
                            continue;
                        }

                        $records = [];
                        if (!$this->testMode) {
                            $this->log($base['name'], 'Writing to database for LIVE mode');
                            $this->repository->update($data, $context);
                            $updated += $count;
                            $this->log(
                                $base['name'],
                                sprintf('Updated %d/%d products', $updated, $total)
                            );
                        } else {
                            $this->log($base['name'], 'Skipping writing to database for TEST mode');
                        }
                    } catch (Exception $e) {
                        $this->error('Error', $e->getMessage());
                    }
                }
            }

            $logMessage = 'Total ' . sprintf('%s/%s products were updated', $updated, $total);
            $this->error($base['name'], $logMessage);
        }

        return $exceptionIds;
    }

    /**
     * @param ProductEntity|SalesChannelProductEntity $productEntity
     * @param array $ruleData
     *
     * @return array
     */
    public function executeRaise(RepricerEntity $repricerEntity, $productEntity, array $ruleData): array
    {
        $productNumber = $productEntity->getProductNumber();
        // $customFields = $productEntity->getCustomFields();
        // $price = $customFields['kit_pvg_price'] ?? null;
        $price = self::getPvgPriceFromProduct($productEntity);
        if (empty($price)) {
            $this->log($ruleData['name'], 'No PVG price found .. skipping', $productNumber);

            return [];
        }

        $this->log($ruleData['name'], 'PVG Price (Original gross price): ' . $price, $productNumber);
        $tax = self::getTax($productEntity);
        $oldGrossPrice = $price;

        $diff = $ruleData['margin'];
        $gapToCompetitor = $ruleData['gapToCompetitor'];
        $secondBestPrice = Utility::priceToFloat($repricerEntity->get('price2'));
        if ($secondBestPrice <= 0) {
            $this->log($ruleData['name'], 'Second best price not found', $productNumber);

            return [];
        }

        $newGrossPrice = round($secondBestPrice - $gapToCompetitor, 2);
        $this->log(
            $ruleData['name'],
            sprintf('New gross price "%s" after competitor difference (%s)', $newGrossPrice, $gapToCompetitor),
            $productNumber
        );

        $logMessage = sprintf(
            'Checking for comparitive difference (%s) between second best price "%s" and current gross price "%s"',
            $diff,
            $secondBestPrice,
            $oldGrossPrice
        );
        $this->log($ruleData['name'], $logMessage, $productNumber);
        $minDifference = ($secondBestPrice - $oldGrossPrice) > $diff;
        // We only make the entry if the difference is greater and prices are different
        try {
            if ($minDifference && $oldGrossPrice !== $newGrossPrice) {
                $this->log($ruleData['name'], 'Difference of ' . $diff . ' is greater', $productNumber);
                $this->log('PRICE UPDATE', '==================== PRICE UPDATED =================', $productNumber);
                $this->setLogEntry(
                    $productNumber,
                    '-',
                    $oldGrossPrice,
                    $newGrossPrice,
                    'raise',
                    $ruleData['name'] . ($this->testMode ? ' - Test Mode' : ''),
                    '-',
                    0,
                    0,
                    sprintf('Minmum Difference: %s / Value of Difference: %s', $diff, $gapToCompetitor)
                );

                $updateMainPrice = (bool)$ruleData['updateRegularPrice'];
                if ($updateMainPrice) {
                    $this->log($ruleData['name'], 'Updating regular price', $productNumber);
                }

                return [
                    'productId' => $productEntity->getId(),
                    'newGrossPrice' => $newGrossPrice,
                    'oldPrice' => $oldGrossPrice,
                    'updateMainPrice' => $updateMainPrice,
                    'tax' => $tax
                ];
            }
        } catch (Exception $e) {
            $this->log('PRICE UPDATE', $e->getMessage(), $productNumber);
            $this->log('PRICE UPDATE', '==================== PRICE UPDATE FAILED =================', $productNumber);
        }

        $this->log($ruleData['name'], 'Min difference was not reached', $productNumber);

        return [];
    }

    /**
     * @param ProductEntity|SalesChannelProductEntity $productEntity
     * @param array $ruleData
     *
     * @return float|mixed|null
     * @throws DBALException
     */
    public function executeSink(RepricerEntity $repricer, $productEntity, array $ruleData): array
    {
        $productNumber = $productEntity->getProductNumber();
        $customFields = $productEntity->getCustomFields();

        if (isset($ruleData['adjustIfInStock']) && $ruleData['adjustIfInStock'] && !$productEntity->getStock()) {
            $this->log($ruleData['name'], 'Skipping due to product not in stock', $productNumber);

            return [];
        }

        $price = self::getPvgPriceFromProduct($productEntity);
        if (empty($price)) {
            $this->log($ruleData['name'], 'No PVG price found .. skipping', $productNumber);

            return [];
        }

        $this->log($ruleData['name'], 'PVG Price (Original gross price): ' . $price, $productNumber);

        $lockedUntil = null;
        $today = new DateTime('now', $this->getTimezone());
        if (isset($customFields['kit_pvg_block'])) {
            $lockedUntil = new DateTime($customFields['kit_pvg_block'], $this->getTimezone());
        }

        $blockInsideBusinessHours = $customFields['kit_inside_business_hour'] ?? null;
        $blockOutsideBusinessHours = $customFields['kit_outside_business_hour'] ?? null;

        // If price needs to be locked outside business hours, and we are inside business hour right now
        // OR
        // If price needs to be locked inside business hours, and we are outside business hour right now
        // == > We will restore the old price in PVG so correct price can be calculated
        if (($blockOutsideBusinessHours && $this->isInsideBusinessHour) ||
            ($blockInsideBusinessHours && !$this->isInsideBusinessHour)
        ) {
            $this->log($ruleData['name'], 'Applying business hour logic');
            $previousPrice = $customFields['kit_pvg_previous_price'] ?? null;
            if ($previousPrice) {
                $this->log($ruleData['name'], 'Current PVG Price: ' . $price, $productNumber);
                $price = $previousPrice;
                $this->log($ruleData['name'], 'Restoring previous PVG Price: ' . $price, $productNumber);
            }
        }

        $tax = self::getTax($productEntity);
        $originalGrossPrice = $price;
        // $originalNetPrice = round($originalGrossPrice / $tax, 2);
        $margin = $ruleData['margin'];

        $purchasePrices = $productEntity->getPurchasePrices();
        if ($purchasePrices && $purchasePrices->first()) {
            $purchasePrice = $purchasePrices->first()->getGross();
        } else {
            $purchasePrice = $productEntity->getPurchasePrice();
        }

        // If there is a minimum price set, we calculate it against this, otherwise use the default logic
        if (isset($customFields['kit_pvg_min_price']) && $customFields['kit_pvg_min_price']) {
            $minPrice = $customFields['kit_pvg_min_price'];
            $this->log($ruleData['name'], sprintf('Using min price "%s" from custom field', $minPrice), $productNumber);
        } else {
            $minPrice = round($purchasePrice * $margin, 2);
            $this->log($ruleData['name'], sprintf('Using min price "%s" from calculation', $minPrice), $productNumber);
        }

        if (!$minPrice) {
            return [];
        }

        for ($i = 1; $i <= 10; $i++) {
            $priceIndex = sprintf('price%d', $i);
            $supplierIndex = sprintf('anbieter%d', $i);
            $stockIndex = sprintf('lz%d', $i);
            $priceFromGeizhal = Utility::priceToFloat($repricer->get($priceIndex));

            // If no price from provider means there is no provider selling the product with it.
            if ($priceFromGeizhal <= 0) {
                continue;
            }

            // conditional statement based on adjustWithCompetitorInventory and competitor stock availability
            if (isset($ruleData['adjustWithCompetitorInventory']) && $ruleData['adjustWithCompetitorInventory']) {
                // if product not in stock then do not update the price.
                if (empty($repricer->get($stockIndex))) {
                    $this->log($ruleData['name'], 'Product not in stock so price was not updated', $productNumber);
                    continue;
                }
            }

            if ($originalGrossPrice >= $priceFromGeizhal) {
                $isSupplierKIT = $this->ifSupplierIsKlarsicht($repricer->get($supplierIndex));
                if ($originalGrossPrice === $priceFromGeizhal && $isSupplierKIT) {
                    $this->log(
                        $ruleData['name'],
                        'Product already has the best price from geizhals: "' . $priceFromGeizhal . '"',
                        $productNumber
                    );
                    break;
                }

                $klarsichtIsBefore = false;

                if ($this->ifSupplierIsKlarsicht($repricer->get($supplierIndex))) {
                    $klarsichtIsBefore = true;
                }

                $newPlace = ($klarsichtIsBefore ? $i - 1 : $i);
                if ($newPlace < 1) {
                    $newPlace = 1;
                }

                if (isset($ruleData['position']) && $newPlace < $ruleData['position'] && $ruleData['position'] > 0) {
                    $this->log($ruleData['name'], 'Optimized for position: ' . $newPlace, $productNumber);
                    continue;
                }

                if ($this->containsExcludedAffiliate($repricer->get($supplierIndex))) {
                    $this->log(
                        $ruleData['name'],
                        'Supplier is excluded in config: ' . $repricer->get($supplierIndex),
                        $productNumber
                    );
                    continue;
                }

                $newGrossPrice = $priceFromGeizhal - $ruleData['gapToCompetitor'];

                if (($newGrossPrice >= $minPrice) && $newGrossPrice < $originalGrossPrice) {
                    // Check if the price is locked until given date, then skip the product from repricer
                    if ($lockedUntil && $today <= $lockedUntil) {
                        $this->log(
                            $ruleData['name'],
                            sprintf(
                                'New price "%s" can be updated at position %d, but repricer is locked until' .
                                ' "%s" so price is not updated',
                                $newGrossPrice,
                                $i,
                                $lockedUntil->format(self::FORMAT)
                            ),
                            $productNumber
                        );
                        continue;
                    }

                    if ($blockInsideBusinessHours && $this->isInsideBusinessHour) {
                        $this->log(
                            $ruleData['name'],
                            sprintf(
                                'New price "%s" can be updated at position %d, but repricer is blocked inside ' .
                                'business hours so price is not updated',
                                $newGrossPrice,
                                $i
                            ),
                            $productNumber
                        );
                        continue;
                    }

                    if ($blockOutsideBusinessHours && !$this->isInsideBusinessHour) {
                        $this->log(
                            $ruleData['name'],
                            sprintf(
                                'New price "%s" can be updated at position %d, but repricer is blocked outside ' .
                                'business hours of next day so price is not updated',
                                $newGrossPrice,
                                $i
                            ),
                            $productNumber
                        );
                        continue;
                    }

                    try {
                        $this->log(
                            'PRICE UPDATE',
                            '==================== PRICE UPDATED =================',
                            $productNumber
                        );
                        $this->setLogEntry(
                            $productNumber,
                            'Platz ' . $i . ', Anbieter ' . $repricer->get($supplierIndex) . ', Preis ' .
                            $repricer->get($priceIndex) . ' Stück ' . $repricer->get($stockIndex),
                            $originalGrossPrice,
                            $newGrossPrice,
                            'sink',
                            $ruleData['name'] . ($this->testMode ? ' - Test Mode' : ''),
                            'Platz ' . $i . ', Anbieter ' . $repricer->get($supplierIndex) . ', Preis ' .
                            $repricer->get($priceIndex) . ' Stück ' . $repricer->get($stockIndex),
                            $newPlace,
                            round($minPrice, 2),
                            $ruleData['marginRaw'] . "%"
                        );

                        $updateMainPrice = (bool)$ruleData['updateRegularPrice'];
                        if ($updateMainPrice) {
                            $this->log($ruleData['name'], 'Updating regular price', $productNumber);
                        }

                        return [
                            'productId' => $productEntity->getId(),
                            'newGrossPrice' => $newGrossPrice,
                            'oldPrice' => $originalGrossPrice,
                            'updateMainPrice' => $updateMainPrice,
                            'tax' => $tax
                        ];
                    } catch (Exception $e) {
                        $this->log('PRICE UPDATE', $e->getMessage(), $productNumber);
                        $this->log(
                            'PRICE UPDATE',
                            '==================== PRICE UPDATE FAILED =================',
                            $productNumber
                        );
                    }
                }

                if ($i !== 10) {
                    $this->log(
                        $ruleData['name'],
                        sprintf(
                            'New price "%s" is less than min price "%s" at position %d, ' .
                            'so we check for next best position',
                            $newGrossPrice,
                            $minPrice,
                            $i
                        ),
                        $productNumber
                    );
                } else {
                    $this->log(
                        $ruleData['name'],
                        sprintf(
                            'New price "%s" is less than min price "%s" at position 10. ' .
                            'No suitable price was found for optimization',
                            $newGrossPrice,
                            $minPrice
                        ),
                        $productNumber
                    );
                }
            } else {
                $this->log(
                    $ruleData['name'],
                    sprintf(
                        "Current gross price '%s' is already less than '%s' price from geizhals at position %d",
                        $originalGrossPrice,
                        $priceFromGeizhal,
                        $i
                    ),
                    $productNumber
                );
                // If we reach here, it means we are already ahead of other positions
                break;
            }
        }

        return [];
    }

    public function getConfig(string $key)
    {
        return $this->configService->get(sprintf('KitAutoPriceUpdate.config.%s', $key));
    }

    private function error(string $title, $message, ?string $productNumber = null): void
    {
        $this->log($title, $message, $productNumber, true);
    }

    private function log(string $title, $message, ?string $productNumber = null, $isError = false): void
    {
        if ($this->environment !== 'dev' && !$isError) {
            return;
        }

        $title = strtoupper($title);
        if (is_array($message)) {
            $info = '';
            foreach ($message as $key => $value) {
                $info .= sprintf('%s: %s | ', $key, trim(strip_tags(str_replace('<', ' <', $value))));
            }
            $message = $info;
        }

        if ($productNumber) {
            $message = sprintf('KitRepricer: %s - %s | %s', $productNumber, $title, $message);
        } else {
            $message = sprintf('KitRepricer: %s | %s', $title, $message);
        }
        $this->logger->debug($message);
        echo $message . PHP_EOL;
    }

    public function getBaseRule($type)
    {
        $sql = <<<SQL
select *, LOWER(HEX(`id`)) as `id` from `kit_priceupdate_base_rules` where `type` = ?
SQL;

        return $this->connection->fetchAssociative($sql, [$type]) ?: [];
    }

    public function getExeptionRules($type): array
    {
        $sql = <<<SQL
select *, LOWER(HEX(`id`)) as `id` from `kit_priceupdate_exception_rules` where `type` = ? and `active` = 1 and `excluded` != 1 order by `priority` desc;
SQL;

        return $this->connection->fetchAllAssociative($sql, [$type]) ?: [];
    }

    public function setLogEntry(
        $productNumber,
        $bestCompetitor,
        $oldPrice,
        $bestPriceWithMargin,
        $action,
        $rulename,
        $related = '',
        $newPlace = '',
        $minPrice = '',
        $percentage = ''
    ): void {
        $this->connection->insert(
            'kit_priceupdate_logs',
            [
                'artId' => $productNumber,
                'bestCompetitor' => $bestCompetitor,
                'oldPrice' => $oldPrice,
                'bestPriceWithMargin' => $bestPriceWithMargin,
                'related_to' => $related,
                'min_price' => $minPrice,
                'new_place' => $newPlace,
                'percentage' => $percentage,
                'action' => $action,
                'rulename' => $rulename,
                'created_at' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ]
        );

        $this->log(
            'Price',
            sprintf(
                'Best Competitor: %s | Old Price: %s | Best Price with margin: %s | Related to: %s | Min Price: %s | New position: %s | Percentage: %s | Action: %s | Rulename: %s',
                $bestCompetitor,
                $oldPrice,
                $bestPriceWithMargin,
                $related,
                $minPrice,
                $newPlace,
                $percentage,
                $action,
                $rulename
            ),
            $productNumber
        );
    }

    private function generateData(array $records): array
    {
        $generatedData = [];
        foreach ($records as $record) {
            $id = $record['productId'];
            $tax = $record['tax'];
            $oldPrice = $record['oldPrice'];
            $newGrossPrice = $record['newGrossPrice'];
            $updateMainPrice = (bool)$record['updateMainPrice'];

            $data = [
                'id' => $id,
                'prices' => [
                    [
                        'id' => md5($id),
                        'quantityStart' => 1,
                        'rule' => [
                            'id' => self::PVG_RULE_ID,
                            'name' => 'PVG',
                            'priority' => 999999
                        ],
                        'price' => [
                            [
                                'currencyId' => Defaults::CURRENCY,
                                'gross' => $newGrossPrice,
                                'net' => round($newGrossPrice / $tax, 2),
                                'linked' => true
                            ]
                        ],
                    ],
                ],
                'customFields' => [
                    'kit_pvg_price' => $newGrossPrice,
                    'kit_pvg_previous_price' => $oldPrice
                ]
            ];

            if ($updateMainPrice) {
                $data['price'] = [
                    [
                        'currencyId' => Defaults::CURRENCY,
                        'gross' => $newGrossPrice,
                        'net' => round($newGrossPrice / $tax, 2),
                        'linked' => true
                    ],
                ];
            }

            $generatedData[] = $data;
        }

        return $generatedData;
    }

    private function ifSupplierIsKlarsicht($supplier): bool
    {
        return (stripos(strtolower($supplier), 'klarsicht') !== false);
    }

    private function containsExcludedAffiliate($supplier): bool
    {
        if (!empty($supplier)) {
            foreach ($this->excludedAffiliates as $company) {
                if (stripos($supplier, $company) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param ProductEntity|SalesChannelProductEntity $productEntity
     */
    public static function getTax($productEntity): float
    {
        // Use 19% tax if no tax entries found
        $taxRate = $productEntity->getTax() ? $productEntity->getTax()->getTaxRate() : 19;

        return ($taxRate + 100) / 100;
    }

    private function createCriteriaFromRule(array $rule): Criteria
    {
        $filters = [];
        $criteria = new Criteria();
        $criteria->addAssociation('product');
        $criteria->addAssociation('product.customFields');
        $criteria->addFilter(new EqualsFilter('product.active', true));

        if (isset($rule['manufacturerIds'])) {
            $manufacturerIds = explode(',', $rule['manufacturerIds']);
            if (!is_array($manufacturerIds)) {
                $manufacturerIds = [$manufacturerIds];
            }
            $manufacturerIds = array_filter($manufacturerIds);
            if (!empty($manufacturerIds)) {
                $filters[] = new EqualsAnyFilter('product.manufacturerId', $manufacturerIds);
            }
        }

        if (isset($rule['categoryIds'])) {
            $criteria->addAssociation('product.categories');
            $categoryIds = explode(',', $rule['categoryIds']);
            if (!is_array($categoryIds)) {
                $categoryIds = [$categoryIds];
            }
            $categoryIds = array_filter($categoryIds);
            if (!empty($categoryIds)) {
                $filters[] = new EqualsAnyFilter('product.categories.id', $categoryIds);
            }
        }

        if (isset($rule['supplierIds'])) {
            $supplierIds = explode(',', $rule['supplierIds']);
            if (!is_array($supplierIds)) {
                $supplierIds = [$supplierIds];
            }
            $supplierIds = array_filter($supplierIds);
            if (!empty($supplierIds)) {
                $supplierIds = array_map(
                    static function ($item) {
                        return (int)$item;
                    },
                    $supplierIds
                );
                $filters[] = new EqualsAnyFilter('product.customFields.kit_product_supplier_id', $supplierIds);
            }
        }

        if (isset($rule['minPrice'])) {
            $filters[] = new RangeFilter('product.price.gross', ['gt' => (float)$rule['minPrice']]);
        }

        if (isset($rule['maxPrice']) && $rule['maxPrice'] > 0) {
            $filters[] = new RangeFilter('product.price.gross', ['lt' => (float)$rule['maxPrice']]);
        }

        if (isset($rule['productNumbers']) && $rule['productNumbers']) {
            $productNumbers = explode(',', str_replace(' ', '', $rule['productNumbers']));
            if (!is_array($productNumbers)) {
                $productNumbers = [$productNumbers];
            }
            $productNumbers = array_filter($productNumbers);
            if (!empty($productNumbers)) {
                $filters[] = new EqualsAnyFilter('product.productNumber', $productNumbers);
            }
        }

        if (isset($rule['adjustIfInStock']) && $rule['adjustIfInStock']) {
            $filters[] = new RangeFilter('product.stock', ['gt' => 0]);
        }

        if (isset($rule['productName']) && $rule['productName']) {
            $productNames = explode(',', $rule['productName']);
            if (!is_array($productNames)) {
                $productNames = [$productNames];
            }
            $productNames = array_filter($productNames);
            $productNameFilter = [];
            foreach ($productNames as $productName) {
                $productNameFilter[] = new ContainsFilter('product.name', $productName);
            }

            if ($productNameFilter) {
                $filters[] = new MultiFilter(MultiFilter::CONNECTION_OR, $productNameFilter);
            }
        }

        if (isset($rule['productDesc']) && $rule['productDesc']) {
            $filters[] = new ContainsFilter('product.description', $rule['productDesc']);
        }

        $notFilters[] = new EqualsFilter('product.customFields.kit_pvg_price', '');
        $notFilters[] = new EqualsFilter('product.customFields.kit_pvg_price', null);
        $filters[] = new NotFilter(
            NotFilter::CONNECTION_AND,
            [
                new MultiFilter(
                    MultiFilter::CONNECTION_AND,
                    $notFilters
                )
            ]
        );

        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, $filters));

        return $criteria;
    }

    private function getTimeZone(): DateTimeZone
    {
        return new DateTimeZone('Europe/Berlin');
    }

    private function getExcludedRuleByType(string $type): array
    {
        $sql = <<<SQL
select *, LOWER(HEX(`id`)) as `id` from `kit_priceupdate_exception_rules` where `type` = ? and `active` = 1 and `excluded` = 1 order by `priority` desc;
SQL;

        return $this->connection->fetchAllAssociative($sql, [$type]) ?: [];
    }

    private function getTaxRule(?string $taxId, string $countryId, SalesChannelContext $context): ?TaxRuleEntity
    {
        if (!$taxId) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('taxId', $taxId));
        $criteria->addFilter(new EqualsFilter('countryId', $countryId));

        return $this->taxRuleRepository->search($criteria, $context->getContext())->first();
    }

    /**
     * @param SalesChannelProductEntity|ProductEntity $product
     * @param SalesChannelContext $context
     *
     * @return float|int
     */
    private function getTaxRateForProductByCountry($product, SalesChannelContext $context)
    {
        $taxId = $product->getTaxId();
        $countryId = $context->getShippingLocation()->getCountry()->getId();
        $taxRule = $this->getTaxRule($taxId, $countryId, $context);
        if ($taxRule) {
            $taxRate = $taxRule->getTaxRate();
            $tax = ($taxRate + 100) / 100;
        } else {
            $tax = self::getTax($product);
        }

        return $tax;
    }

    /**
     * Helper method that calculates the gross price based on country tax. Either use pvg (DEFAULT)
     *  or cp (Campus Product) so it will add the tax on top of it for display in frontend.
     *
     * @param SalesChannelProductEntity|ProductEntity $product
     * @param SalesChannelContext $context
     *
     * @return array
     */
    public function calculateGrossPriceBasedOnCountryTax(
        $product,
        SalesChannelContext $context,
        ?string $type = 'pvg'
    ): array {
        if ($type === 'cp') {
            $gross = self::getCampusPriceFromProduct($product);
        } else {
            $gross = self::getPvgPriceFromProduct($product);
        }

        // If price is 0, we throw an exception so it fallbacks to the default Shopware Price.
        if(!$gross) {
            throw new RuntimeException('Calculated price is 0');
        }

        $productTax = self::getTax($product);
        $countryTax = $this->getTaxRateForProductByCountry($product, $context);
        // Remove product tax to get base gross price
        $gross /= $productTax;
        // Add country tax to get correct gross price
        $gross *= $countryTax;

        return ['gross' => $gross, 'countryTax' => $countryTax];
    }

    /**
     * @param SalesChannelProductEntity|ProductEntity $product
     */
    public static function getPvgPriceFromProduct($product)
    {
        $customFields = $product->getCustomFields();

        return $customFields['kit_pvg_price'] ?? 0;
    }

    /**
     * @param SalesChannelProductEntity|ProductEntity $product
     */
    public static function getCampusPriceFromProduct($product)
    {
        $customFields = $product->getCustomFields();

        return $customFields['kit_campus_price'] ?? 0;
    }

    public function getProductFromLineItem(LineItem $lineItem, Context $context): ?ProductEntity
    {
        $criteria = new Criteria([$lineItem->getReferencedId()]);
        return $this->repository->search($criteria, $context)->first();
    }
}
