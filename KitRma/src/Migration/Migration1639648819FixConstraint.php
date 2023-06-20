<?php declare(strict_types=1);

namespace KitRma\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1639648819FixConstraint extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1639648819;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `rma_ticket`
CHANGE `product_serial_numbers` `product_serial_numbers` json NULL AFTER `ticket_serial_number`
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
