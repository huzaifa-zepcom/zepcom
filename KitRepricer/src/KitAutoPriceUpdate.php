<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate;

use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

use function md5;

class KitAutoPriceUpdate extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        try {
            $this->createKitPvgFields($installContext->getContext());
            $this->createKitCustomFields($installContext->getContext());
        } catch (Exception $e) {
        }
    }

    private function createKitCustomFields(Context $context): void
    {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field_set.repository');
        $customFieldRepository->upsert(
            [
                [
                    'id' => md5('kit_customer'),
                    'name' => 'kit_customer',
                    'customFields' => [
                        [
                            'id' => md5('kit_customer_pvg'),
                            'name' => 'kit_customer_pvg',
                            'type' => CustomFieldTypes::BOOL,
                            'config' => [
                                'componentName' => 'sw-field',
                                'type' => 'checkbox',
                                'customFieldType' => 'checkbox',
                                'label' => [
                                    'en-GB' => 'PVG Enabled',
                                    'de-DE' => 'PVG Enabled',
                                ],
                                'helpText' => [
                                    'en-GB' => 'Enable to show PVG prices to this customer by default',
                                    'de-DE' => 'Enable to show PVG prices to this customer by default',
                                ],
                                'customFieldPosition' => 1,
                            ]
                        ],
                    ],
                    'relations' => [
                        ['entityName' => 'customer'],
                    ],
                ]
            ],
            $context
        );
    }

    private function createKitPvgFields(Context $context): void
    {
        /** @var EntityRepositoryInterface $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field_set.repository');
        $customFieldRepository->upsert(
            [
                [
                    'id' => md5('kit_pvg_block'),
                    'name' => 'kit_pvg_block',
                    'customFields' => [
                        [
                            'id' => md5('kit_pvg_previous_price'),
                            'name' => 'kit_pvg_previous_price',
                            'type' => CustomFieldTypes::FLOAT,
                            'config' => [
                                'type' => 'number',
                                'componentName' => 'sw-field',
                                'customFieldType' => 'number',
                                'numberType' => 'float',
                                'disabled' => true,
                                'label' => [
                                    'en-GB' => 'Previous Price',
                                    'de-DE' => 'Previous Price',
                                ],
                                'helpText' => [
                                    'en-GB' => 'Previously calculated PVG price (read-only)',
                                    'de-DE' => 'Previously calculated PVG price (read-only)',
                                ],
                                'customFieldPosition' => 9,
                            ]
                        ],
                        [
                            'id' => md5('kit_pvg_min_price'),
                            'name' => 'kit_pvg_min_price',
                            'type' => CustomFieldTypes::FLOAT,
                            'config' => [
                                'type' => 'number',
                                'componentName' => 'sw-field',
                                'customFieldType' => 'number',
                                'numberType' => 'float',
                                'label' => [
                                    'en-GB' => 'Min Price',
                                    'de-DE' => 'Min Price',
                                ],
                                'helpText' => [
                                    'en-GB' => 'Do not reprice under this price until date below',
                                    'de-DE' => 'Do not reprice under this price until date below',
                                ],
                                'customFieldPosition' => 10,
                            ]
                        ],
                        [
                            'id' => md5('kit_pvg_block'),
                            'name' => 'kit_pvg_block',
                            'type' => CustomFieldTypes::DATETIME,
                            'config' => [
                                'type' => 'date',
                                'componentName' => 'sw-field',
                                'customFieldType' => 'date',
                                'dateType' => 'date',
                                'label' => [
                                    'en-GB' => 'Block Repricer until',
                                    'de-DE' => 'Block Repricer until',
                                ],
                                'helpText' => [
                                    'en-GB' => 'Set date to block the repricer till',
                                    'de-DE' => 'Set date to block the repricer till',
                                ],
                                'customFieldPosition' => 11,
                            ]
                        ],
                        [
                            'id' => md5('kit_inside_business_hour'),
                            'name' => 'kit_inside_business_hour',
                            'type' => CustomFieldTypes::BOOL,
                            'config' => [
                                'componentName' => 'sw-field',
                                'type' => 'checkbox',
                                'customFieldType' => 'checkbox',
                                'label' => [
                                    'en-GB' => 'Inside Business Hours',
                                    'de-DE' => 'Inside Business Hours',
                                ],
                                'helpText' => [
                                    'en-GB' => 'Do not reprice this product inside business hours',
                                    'de-DE' => 'Do not reprice this product inside business hours',
                                ],
                                'customFieldPosition' => 12,
                            ]
                        ],
                        [
                            'id' => md5('kit_outside_business_hour'),
                            'name' => 'kit_outside_business_hour',
                            'type' => CustomFieldTypes::BOOL,
                            'config' => [
                                'componentName' => 'sw-field',
                                'type' => 'checkbox',
                                'customFieldType' => 'checkbox',
                                'label' => [
                                    'en-GB' => 'Outside Business Hours',
                                    'de-DE' => 'Outside Business Hours',
                                ],
                                'helpText' => [
                                    'en-GB' => 'Do not reprice this product outside business hours',
                                    'de-DE' => 'Do not reprice this product outside business hours',
                                ],
                                'customFieldPosition' => 13,
                            ]
                        ]
                    ],
                    'relations' => [
                        ['entityName' => 'product'],
                    ],
                ]
            ],
            $context
        );
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        if($uninstallContext->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `kit_priceupdate`');
        $connection->executeStatement('DROP TABLE IF EXISTS `kit_priceupdate_logs`');
        // $connection->executeStatement('DROP TABLE IF EXISTS `kit_priceupdate_base_rules`');
        // $connection->executeStatement('DROP TABLE IF EXISTS `kit_priceupdate_exception_rules`');

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetRepository->delete(
            [
                ['id' => md5('kit_customer')],
                ['id' => md5('kit_pvg_block')]
            ],
            $uninstallContext->getContext()
        );
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        try {
            if (\version_compare($updateContext->getUpdatePluginVersion(), '2.0.1', 'eq')) {
                $repo = $this->container->get('custom_field.repository');
                $repo->delete(
                    [
                        ['id' => md5('kit_pvg_link')],
                    ],
                    $updateContext->getContext()
                );
            }
        } catch (Exception $e) {
        }
    }
}
