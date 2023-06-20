<?php declare(strict_types=1);

namespace NetzBubeckMigration\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1662445280BlogSw5Data extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1662445280;
    }

    public function update(Connection $connection): void
    {
        $sql = file_get_contents(__DIR__ .'/../Database/Blog.sql');

        $connection->executeStatement($sql);

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
