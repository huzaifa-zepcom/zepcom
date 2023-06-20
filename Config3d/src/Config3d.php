<?php declare(strict_types=1);

namespace Config3d;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class Config3d extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->createCustomFields(Context::createDefaultContext());
    }

    private function createCustomFields(Context $context): void
    {
        $customFieldRepository = $this->container->get('custom_field_set.repository');
        $customFieldIds = $this->getCustomFieldIds($context);

        if ($customFieldIds->getTotal() !== 0) {
            return;
        }

        $customFieldRepository->upsert(
            [
                [
                    'name' => 'config3d',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Config3d'
                        ]
                    ],
                    'customFields' => [
                        [
                            'name' => 'customization_config_url',
                            'label' => 'Configuration URL',
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'componentName' => 'sw-url-field',
                                'type' => 'text',
                                'customFieldType' => 'text',
                                'label' => 'Configuration URL',
                                'customFieldPosition' => 1
                            ]
                        ],
                    ],
                    'relations' => [
                        ['entityName' => 'product'],
                    ],
                ]
            ], $context
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
                    new EqualsFilter('name', 'config3d')
                ]
            )
        );

        return $customFieldRepository->searchIds($criteria, $context);
    }

    public function uninstall(UninstallContext $unContext): void
    {
        if ($unContext->keepUserData()) {
            return;
        }

        parent::uninstall($unContext);
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `config3d_plugin`');

        $this->deleteCustomFields(Context::createDefaultContext());
    }

    private function deleteCustomFields(Context $context): void
    {
        $customFieldRepository = $this->container->get('custom_field_set.repository');
        $customFieldIds = $this->getCustomFieldIds($context);

        if ($customFieldIds->getTotal() === 0) {
            return;
        }

        $ids = \array_map(static function ($id) {
            return ['id' => $id];
        }, $customFieldIds->getIds());

        $customFieldRepository->delete($ids, $context);
    }
}