<?php

declare(strict_types=1);

namespace NetzBirthdayReminder;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class NetzBirthdayReminder extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->createCustomField($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);

        $this->removeEmailTemplate($connection);
    }

    private function removeEmailTemplate(Connection $connection): void
    {
        $mailTemplateTypeId = '677bb3d7c1014055631cc9306c789cb7';

        try {
            $connection->delete('mail_template_type', [
                'id' => Uuid::fromHexToBytes($mailTemplateTypeId)
            ]);
        } catch (\Exception $e) {
        }

        try {
            $connection->delete('mail_template_type_translation', [
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId)
            ]);
        } catch (\Exception $e) {
        }

        $this->deleteMailTemplate($connection, $mailTemplateTypeId);
    }

    private function deleteMailTemplate($connection, string $mailTemplateTypeId): void
    {
        $mailTemplateId = '76aff5086352a181890f44bba27254ea';

        try {
            $connection->delete('mail_template', [
                'id' => Uuid::fromHexToBytes($mailTemplateId)
            ]);
        } catch (\Exception $e) {
        }

        try {
            $connection->delete('mail_template_translation', [
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId)
            ]);
        } catch (\Exception $e) {
        }

        $this->deleteTemplateFromSalesChannels($connection, $mailTemplateTypeId, $mailTemplateId);
    }

    private function deleteTemplateFromSalesChannels(
        Connection $connection,
        string $mailTemplateTypeId,
        string $mailTemplateId
    ): void {
        $connection->delete(
            'mail_template_sales_channel',
            [
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId)
            ]
        );
    }

    private function createCustomField(Context $context): void
    {
        $customFieldRepository = $this->container->get('custom_field_set.repository');
        $customFieldIds = $this->getCustomFieldIds($context);

        if ($customFieldIds->getTotal() !== 0) {
            return;
        }

        $customFieldRepository->upsert(
            [
                [
                    'name' => 'netz_custom_fields',
                    'label' => [
                        'en-GB' => 'Netzturm Additional Fields',
                        'de-DE' => 'Netzturm Additional Fields',
                    ],
                    'customFields' => [
                        [
                            'name' => 'netz_dog_name',
                            'label' => [
                                'en-GB' => 'Dog name',
                                'de-DE' => 'Hundename'
                            ],
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'componentName' => 'sw-field',
                                'type' => 'text',
                                'customFieldType' => 'text',
                                'label' => [
                                    'en-GB' => 'Dog name',
                                    'de-DE' => 'Hundename'
                                ],
                                'customFieldPosition' => 1
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

    private function getCustomFieldIds(Context $context): IdSearchResult
    {
        $customFieldRepository = $this->container->get('custom_field_set.repository');
        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(
                'OR',
                [
                    new EqualsFilter('name', 'netz_custom_fields'),
                ]
            )
        );

        return $customFieldRepository->searchIds($criteria, $context);
    }
}
