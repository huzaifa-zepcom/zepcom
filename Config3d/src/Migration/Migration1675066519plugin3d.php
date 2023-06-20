<?php declare(strict_types=1);

namespace Config3d\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1675066519plugin3d extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1675066519;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `config3d_plugin` (
  `id` binary(16) NOT NULL,
  `line_item_id` binary(16) NOT NULL,
  `product_id` binary(16) NOT NULL,
  `order_id` binary(16) NOT NULL,
  `config_data` json NOT NULL,
  `try_attempt_number` int unsigned DEFAULT NULL,
  `next_attempt_at` datetime(3) DEFAULT NULL,
  `response_status` int unsigned DEFAULT NULL,
  `response_data` text COLLATE utf8mb4_unicode_ci,
  `failed` tinyint unsigned DEFAULT NULL,
  `created_at` datetime(3) NOT NULL,
  `updated_at` datetime(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_item_id` (`line_item_id`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
