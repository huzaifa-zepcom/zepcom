<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1605695116 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1605695116;
    }

    public function update(Connection $connection): void
    {
        $this->executePriceTableQury($connection);
        $this->executeLogsTableQuery($connection);
        $this->executeBaseRulesTableQuery($connection);
        $this->executeExceptionRulesTableQuery($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function executeLogsTableQuery(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `kit_priceupdate_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `artId` text CHARACTER SET utf8mb4 NOT NULL,
  `bestCompetitor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oldPrice` double NOT NULL,
  `bestPriceWithMargin` double NOT NULL,
  `related_to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_price` double NOT NULL,
  `new_place` int(11) NOT NULL,
  `percentage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` text CHARACTER SET utf8mb4,
  `rulename` text CHARACTER SET utf8mb4,
  `created_at` datetime(3),
  PRIMARY KEY (`id`),
  FULLTEXT KEY `artId` (`artId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    private function executePriceTableQury(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `kit_priceupdate` (
  `id` binary(16) NOT NULL,
  `product_id` binary(16) NOT NULL,
  `geizhalsID` int unsigned NOT NULL,
  `geizhalsArtikelname` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meinPreis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `price1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter1` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz1` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz2` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz3` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price4` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter4` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz4` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price5` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter5` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz5` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price6` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter6` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz6` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price7` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter7` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz7` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price8` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter8` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz8` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price9` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter9` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz9` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price10` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anbieter10` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lz10` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meineArtikelnummer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `geizhalsArtikelURL` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime(3) DEFAULT NULL,
  `updated_at` datetime(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meineArtikelnummer` (`meineArtikelnummer`),
  KEY `idx.repricer_product` (`product_id`),
  CONSTRAINT `fk.repricer.product_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    private function executeBaseRulesTableQuery(Connection $connection)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `kit_priceupdate_base_rules` (
  `id` binary(16) NOT NULL,
  `manufacturerIds` text CHARACTER SET utf8mb4,
  `supplierIds` text CHARACTER SET utf8mb4,
  `categoryIds` text CHARACTER SET utf8mb4,
  `minPrice` DOUBLE NOT NULL,
  `minMargin` DOUBLE NOT NULL,
  `gapToCompetitor` DOUBLE NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4,
  `created_at` datetime(3),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    private function executeExceptionRulesTableQuery(Connection $connection)
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `kit_priceupdate_exception_rules` (
  `id` binary(16) NOT NULL,
  `name` VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL,
  `manufacturerIds` text CHARACTER SET utf8mb4,
  `supplierIds` text CHARACTER SET utf8mb4,
  `categoryIds` text CHARACTER SET utf8mb4,
  `productNumbers` text CHARACTER SET utf8mb4,
  `productName` text CHARACTER SET utf8mb4,
  `productDesc` text CHARACTER SET utf8mb4,
  `minPrice` DOUBLE NOT NULL,
  `maxPrice` DOUBLE NULL,
  `priority` int(11) NOT NULL,
  `excluded` BOOLEAN NULL,
  `adjustIfInStock` BOOLEAN NOT NULL,
  `adjustWithCompetitorInventory` BOOLEAN NOT NULL,
  `active` BOOLEAN NOT NULL,
  `minMargin` DOUBLE NULL,
  `gapToCompetitor` DOUBLE NULL,
  `position` int(2) NULL,
  `type` VARCHAR(20) CHARACTER SET utf8mb4,
  `created_at` datetime(3),
  PRIMARY KEY (`id`),
  FULLTEXT KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }
}
