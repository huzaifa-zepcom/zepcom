<?php declare(strict_types=1);

namespace KitAutoPriceUpdate\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1644231756UpdateNormalPrice extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1644231756;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `kit_priceupdate_base_rules` ADD `updateRegularPrice` tinyint(1) NULL AFTER `type`');
        $connection->executeStatement('ALTER TABLE `kit_priceupdate_exception_rules` ADD `updateRegularPrice` tinyint(1) NULL AFTER `type`');
        $connection->executeStatement('UPDATE kit_priceupdate_base_rules SET `updateRegularPrice` = 1 WHERE `type` = ?', ['raise']);
        $connection->executeStatement('UPDATE kit_priceupdate_exception_rules SET `updateRegularPrice` = 1 WHERE `type` = ?', ['raise']);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
