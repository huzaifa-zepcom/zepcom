<?php declare(strict_types=1);

namespace NetzBubeckMigration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class NetzBubeckMigration extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `s_blog_tags`');
        $connection->executeStatement('DROP TABLE IF EXISTS `s_blog_media`');
        $connection->executeStatement('DROP TABLE IF EXISTS `s_blog_comments`');
        $connection->executeStatement('DROP TABLE IF EXISTS `s_blog_attributes`');
        $connection->executeStatement('DROP TABLE IF EXISTS `s_blog_assigned_articles`');
        $connection->executeStatement('DROP TABLE IF EXISTS `s_blog`');

        $connection->executeStatement('DROP TABLE IF EXISTS `s_neti_storelocator_stock`');
        $connection->executeStatement('DROP TABLE IF EXISTS `s_neti_storelocator_references`');
        $connection->executeStatement('DROP TABLE IF EXISTS `s_neti_storelocator_contactform`');
        $connection->executeStatement('DROP TABLE IF EXISTS `s_neti_storelocator`');


    }
}