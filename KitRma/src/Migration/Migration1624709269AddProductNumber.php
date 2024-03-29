<?php

declare(strict_types=1);

namespace KitRma\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1624709269AddProductNumber extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1624709269;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rma_ticket`
ADD `product_number` varchar(255) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `product_name`;
SQL;

        try {
            $connection->executeStatement($sql);
        } catch (Exception $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
