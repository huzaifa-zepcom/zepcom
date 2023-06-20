<?php

declare(strict_types=1);

namespace KitRma\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1621951874SerialNumber extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1621951874;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rma_ticket`
ADD `serial_number` int(4) NOT NULL AFTER `rma_number`;
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
