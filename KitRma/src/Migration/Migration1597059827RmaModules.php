<?php
declare(strict_types=1);

namespace KitRma\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1597059827RmaModules extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1597059827;
    }

    public function update(Connection $connection): void
    {
        $this->createTables($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function createTables(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `rma_address_book` (
  `id` BINARY(16) NOT NULL,
  `name` TEXT NOT NULL,
  `address` TEXT NOT NULL,
  `suppliers` json default null,
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `rma_case` (
  `id` BINARY(16) NOT NULL,
  `name` TEXT NOT NULL,
  `freetext` json default null,
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `rma_status` (
  `id` BINARY(16) NOT NULL,
  `name` TEXT NOT NULL,
  `name_ext` TEXT NOT NULL,
  `endstate` INT(1) NOT NULL DEFAULT 0,
  `endstate_final` INT(1) NOT NULL DEFAULT 0,
  `color` TEXT NOT NULL,
   `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `name_ext` (`name_ext`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `rma_text` (
  `id` BINARY(16) NOT NULL,
  `name` TEXT CHARACTER SET utf8mb4 DEFAULT NULL,
  `description` TEXT CHARACTER SET utf8mb4 DEFAULT NULL,
  `type` VARCHAR(255) NOT NULL,
   `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `rma_ticket` (
  `id` BINARY(16) NOT NULL,
  `case_id` BINARY(16) DEFAULT NULL,
  `rule_id` VARCHAR(32) DEFAULT NULL,
  `customer_id` BINARY(16) DEFAULT NULL,
  `order_id` BINARY(16) DEFAULT NULL,
  `product_id` BINARY(16) DEFAULT NULL,
  `status_id` BINARY(16) DEFAULT NULL,
  `amount` VARCHAR(11) NOT NULL,
  `supplier_id` BINARY(16),
  `badges` TEXT COLLATE utf8mb4_unicode_ci,
  `ticket_content` json DEFAULT NULL,
  `files` json DEFAULT NULL,
  `delivery_address` TEXT COLLATE utf8mb4_unicode_ci NULL,
  `rma_number` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional_info` TEXT COLLATE utf8mb4_unicode_ci NULL,
  `restocking_fee_customer` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
  `restocking_fee_supplier` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
  `kit_voucher` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
  `supplier_voucher` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
  `customer_email` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
  `link` TEXT COLLATE utf8mb4_unicode_ci NULL,
  `hash` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
  `user_id` BINARY(16) DEFAULT NULL,
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rma_number` (`rma_number`(255)),
  INDEX `idx.ticket_user` (`user_id`),
  INDEX `idx.ticket_customer` (`customer_id`),
  INDEX `idx.ticket_product` (`product_id`),
  INDEX `idx.ticket_order` (`order_id`),
  INDEX `idx.ticket_case` (`case_id`),
  INDEX `idx.supplier_id` (`supplier_id`),
  INDEX `idx.ticket_status` (`status_id`),
  INDEX `idx.ticket_hash` (`hash`),
  CONSTRAINT `fk.ticket_user.user_id` FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk.ticket_customer.customer_id` FOREIGN KEY (`customer_id`)
    REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk.ticket_product.product_id` FOREIGN KEY (`product_id`)
    REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk.ticket_order.order_id` FOREIGN KEY (`order_id`)
    REFERENCES `order` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk.ticket_status.status_id` FOREIGN KEY (`status_id`)
    REFERENCES `rma_status` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk.ticket_supplier.supplier_id` FOREIGN KEY (`supplier_id`)
    REFERENCES `kit_supplier` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk.ticket_case.case_id` FOREIGN KEY (`case_id`)
    REFERENCES `rma_case` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `rma_ticket_history` (
  `id` BINARY(16) NOT NULL,
  `ticket_id` BINARY(16) NOT NULL,
  `sender` VARCHAR(32) NOT NULL DEFAULT 'KLARSICHT',
  `type` VARCHAR(32) NOT NULL DEFAULT 'internal',
  `message` TEXT NOT NULL,
  `attachment` json default NULL,
  `read` TINYINT(1) DEFAULT 0,
  `status_id` BINARY(16) DEFAULT NULL,
  `user_id` BINARY(16) DEFAULT NULL,
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY (`id`),
  INDEX `idx.ticket_history_user` (`user_id`),
  INDEX `idx.ticket_history_ticket` (`ticket_id`),
  INDEX `idx.ticket_history_status` (`status_id`),
   CONSTRAINT `fk.ticket_history.ticket_id` FOREIGN KEY (`ticket_id`)
    REFERENCES `rma_ticket` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
   CONSTRAINT `fk.ticket_history.status_id` FOREIGN KEY (`status_id`)
    REFERENCES `rma_status` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
   CONSTRAINT `fk.ticket_history.user_id` FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }
}
