<?php
declare(strict_types=1);

namespace NetzBubeckMigration\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;

class StoreLocatorMigrationService
{

    private Connection $connection;
    private EntityRepositoryInterface $storeRepository;

    public function __construct(Connection $connection, EntityRepositoryInterface $storeRepository)
    {
        $this->connection = $connection;
        $this->storeRepository = $storeRepository;
    }

    public function execute()
    {
        $context = Context::createDefaultContext();
        $data = [];

        // SQL query to fetch country mappings
        $sql = <<<SQL
SELECT old_identifier, LOWER(HEX(`entity_uuid`)) AS `country_id`
FROM `swag_migration_mapping`
WHERE `entity` = 'country'
SQL;

        // Fetch the country mappings from the database
        $countryMapping = $this->connection->fetchAllKeyValue($sql);

        // Fetch the store data from the database
        $storeData = $this->connection->fetchAllAssociative('SELECT * FROM `s_neti_storelocator`');

        foreach ($storeData as $row) {
            if (!$row['countryID']) {
                $row['countryID'] = 2;
            }

            $data[] = [
                'id' => Uuid::fromStringToHex($row['id']),
                'label' => $row['name'],
                'street' => $row['street'],
                'streetNumber' => $row['number'],
                'zipCode' => $row['zip'],
                'city' => $row['city'],
                'longitude' => $row['lng'],
                'latitude' => $row['lat'],
                'phone' => $row['phone'],
                'fax' => $row['fax'],
                'url' => $row['url'],
                'email' => $row['email'],
                'countryId' => $countryMapping[(int)$row['countryID']],
                'active' => (bool)$row['active'],
                'contactFormEnabled' => (bool)$row['contact'],
                'detailPageEnabled' => (bool)$row['detail_page_enabled'],
                'hidden' => (bool)$row['hidestore'],
                'notificationMailAddress' => $row['notifyemail'],
                'showAlways' => $row['alwaysdisplay'] ? 'yes' : 'no',
                'zoom' => (int)$row['zoom'],
                'excludeFromSync' => (bool)$row['excluded_from_update'],
                'googlePlaceID' => $row['place_id'],
                'featured' => (bool)$row['featured'],
                'radius' => (int)$row['radius'],
                'description' => $row['description'],
                'openingTimes' => nl2br($row['businesshours']),
            ];
        }

        if ($data) {
            // Upsert the store data
            $this->storeRepository->upsert($data, $context);
        }
    }

}