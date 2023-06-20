<?php

declare(strict_types=1);

namespace KitRma;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use KitRma\Helper\Utility;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;

class KitRma extends Plugin
{
    public const PDF_IMAGES_FOLDER = '/warenbegleitschein';

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->setupImages();
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $imagesPath = $this->container->getParameter('shopware.filesystem.public.config.root') . self::PDF_IMAGES_FOLDER;
        $this->rrmdir($imagesPath);
        $this->setupImages();

        if (\version_compare($updateContext->getUpdatePluginVersion(), '4.1.3', '>=')) {
            $connection = $this->container->get(Connection::class);
            $this->removeEmailTemplate($connection);
            $this->createMailTemplateTypes($connection);
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `rma_ticket_history`');
        $connection->executeStatement('DROP TABLE IF EXISTS `rma_ticket`');
        // $connection->executeStatement('DROP TABLE IF EXISTS `rma_case`');
        // $connection->executeStatement('DROP TABLE IF EXISTS `rma_rules`');
        // $connection->executeStatement('DROP TABLE IF EXISTS `rma_text`');
        // $connection->executeStatement('DROP TABLE IF EXISTS `rma_address_book`');
        // $connection->executeStatement('DROP TABLE IF EXISTS `rma_status`');

        $this->removeEmailTemplate($connection);

        $imagesPath = $this->container->getParameter('shopware.filesystem.public.config.root') . self::PDF_IMAGES_FOLDER;
        $this->rrmdir($imagesPath);
    }

    private function removeEmailTemplate(Connection $connection): void
    {
        foreach (Utility::getTemplatesData() as $key => $data) {
            $mailTemplateTypeId = md5($key);

            $connection->delete('mail_template_type', [
                'id' => Uuid::fromHexToBytes($mailTemplateTypeId)
            ]);
            $connection->delete('mail_template_type_translation', [
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId)
            ]);
            $this->deleteMailTemplate($connection, $mailTemplateTypeId, $key);
        }
    }

    private function deleteMailTemplate($connection, string $mailTemplateTypeId, string $key): void
    {
        $mailTemplateId = md5(sprintf('%s.template', $key));

        $connection->delete('mail_template', [
            'id' => Uuid::fromHexToBytes($mailTemplateId)
        ]);
        $connection->delete('mail_template_translation', [
            'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId)
        ]);

        $this->deleteTemplateFromSalesChannels($connection, $mailTemplateTypeId, $mailTemplateId);
    }

    private function deleteTemplateFromSalesChannels(
        Connection $connection,
        string $mailTemplateTypeId,
        string $mailTemplateId
    ): void {
        $connection->delete(
            'mail_template_sales_channel',
            [
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId)
            ]
        );
    }

    private function setupImages(): void
    {
        $imageFolder = $this->getPath() . '/Warenbegleitschein/images/';
        if (is_dir($imageFolder)) {
            $newPath = $this->container->getParameter('shopware.filesystem.public.config.root') . self::PDF_IMAGES_FOLDER;
            $this->rcopy($imageFolder, $newPath);
        }
    }

    private function rrmdir($dir): void
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    $this->rrmdir("$dir/$file");
                }
            }
            rmdir($dir);
        } elseif (file_exists($dir)) {
            unlink($dir);
        }
    }

    private function rcopy($src, $dst): void
    {
        if (file_exists($dst)) {
            $this->rrmdir($dst);
        }
        if (is_dir($src)) {
            if (!mkdir($dst) && !is_dir($dst)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dst));
            }
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    $this->rcopy("$src/$file", "$dst/$file");
                }
            }
        } elseif (file_exists($src)) {
            copy($src, $dst);
        }
    }

    private function createMailTemplateTypes(Connection $connection): void
    {
        foreach (Utility::getTemplatesData() as $key => $data) {
            $mailTemplateTypeId = md5($key);

            $connection->insert('mail_template_type', [
                'id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'technical_name' => $key,
                'available_entities' => json_encode(['salesChannel' => 'sales_channel']),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);

            $connection->insert('mail_template_type_translation', [
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'language_id' => $this->getLanguageIdByLocale($connection, 'de-DE'),
                'name' => $data['name'],
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
            $connection->insert('mail_template_type_translation', [
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'language_id' => $this->getLanguageIdByLocale($connection, 'en-GB'),
                'name' => $data['name'],
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);

            $data['key'] = $key;
            $this->createMailTemplate($connection, $mailTemplateTypeId, $data);
        }
    }

    private function getLanguageIdByLocale(Connection $connection, string $locale): string
    {
        $sql = <<<SQL
select `language`.`id` 
from `language` 
inner join `locale` on `locale`.`id` = `language`.`locale_id`
where `locale`.`code` = :code
SQL;

        $languageId = $connection->fetchOne($sql, ['code' => $locale]);
        if (!$languageId) {
            throw new \RuntimeException(sprintf('Language for locale "%s" not found.', $locale));
        }

        return $languageId;
    }

    private function createMailTemplate(Connection $connection, string $mailTemplateTypeId, array $data): void
    {
        $mailTemplateId = md5(sprintf('%s.template', $data['key']));

        try {
            $connection->insert('mail_template', [
                'id' => Uuid::fromHexToBytes($mailTemplateId),
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'system_default' => false,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (Exception $e) {
            $connection->insert('mail_template', [
                'id' => Uuid::fromHexToBytes($mailTemplateId),
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'system_default' => 0,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        $connection->insert('mail_template_translation', [
            'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
            'language_id' => $this->getLanguageIdByLocale($connection, 'de-DE'),
            'sender_name' => 'KLARSICHT IT Reklamations-Abteilung',
            'subject' => $data['subject'],
            'description' => '',
            'content_html' => $this->getContent($data['key']),
            'content_plain' => $this->getContent($data['key'], true),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        $connection->insert('mail_template_translation', [
            'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
            'language_id' => $this->getLanguageIdByLocale($connection, 'en-GB'),
            'sender_name' => 'KLARSICHT IT Complaints Department',
            'subject' => $data['subject'],
            'description' => '',
            'content_html' => $this->getContent($data['key']),
            'content_plain' => $this->getContent($data['key'], true),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $this->addTemplateToSalesChannels($connection, $mailTemplateTypeId, $mailTemplateId);
    }

    private function getContent(string $name, bool $isPlain = false): ?string
    {
        $html = \file_get_contents(__DIR__ . '/EmailTemplates/' . sprintf('%s.html.twig', $name));
        if (!$html) {
            return null;
        }

        if ($isPlain) {
            $breaks = ["<br />", "<br>", "<br/>"];
            $html = str_ireplace($breaks, "\r\n", $html);

            return \strip_tags($html, '<a>');
        }

        return $html;
    }

    private function addTemplateToSalesChannels(
        Connection $connection,
        string $mailTemplateTypeId,
        string $mailTemplateId
    ): void {
        $salesChannels = $connection->fetchAllAssociative('SELECT `id` FROM `sales_channel` ');

        foreach ($salesChannels as $salesChannel) {
            $mailTemplateSalesChannel = [
                'id' => Uuid::randomBytes(),
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'sales_channel_id' => $salesChannel['id'],
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ];

            $connection->insert('mail_template_sales_channel', $mailTemplateSalesChannel);
        }
    }
}
