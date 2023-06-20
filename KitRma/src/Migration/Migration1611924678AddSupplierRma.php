<?php

declare(strict_types=1);

namespace KitRma\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1611924678AddSupplierRma extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1611924678;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rma_ticket` 
    ADD `supplier_rma_number` varchar(255) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `supplier_voucher`
SQL;

        try {
            $connection->executeStatement($sql);
        } catch (\Exception $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
