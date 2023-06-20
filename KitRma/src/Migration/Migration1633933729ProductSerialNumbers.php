<?php declare(strict_types=1);

namespace KitRma\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1633933729ProductSerialNumbers extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1633933729;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rma_ticket`
CHANGE `serial_number` `ticket_serial_number` int(4) NOT NULL AFTER `rma_number`,
ADD `product_serial_numbers` json NOT NULL AFTER `ticket_serial_number`;
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
