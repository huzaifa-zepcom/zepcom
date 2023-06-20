<?php declare(strict_types=1);

namespace KitRma\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1617896645 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1617896645;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rma_ticket`
ADD `product_name` varchar(255) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `product_id`
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
