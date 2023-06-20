<?php

declare(strict_types=1);

namespace NetzBirthdayReminder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1661257659EmailTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1661257659;
    }

    public function update(Connection $connection): void
    {
        try {
            $this->createMailTemplateTypes($connection);
        } catch (\Exception $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function createMailTemplateTypes(Connection $connection): void
    {
        $mailTemplateTypeId = '677bb3d7c1014055631cc9306c789cb7';

        try {
            $connection->insert('mail_template_type', [
                'id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'technical_name' => 'netz_birthday_reminder',
                'available_entities' => json_encode(['salesChannel' => 'sales_channel']),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (DBALException $e) {
        }

        try {
            $connection->insert('mail_template_type_translation', [
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'language_id' => $this->getLanguageIdByLocale($connection, 'de-DE'),
                'name' => 'NetzBirthdayReminder',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (DBALException $e) {
        }

        try {
            $connection->insert('mail_template_type_translation', [
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'language_id' => $this->getLanguageIdByLocale($connection, 'en-GB'),
                'name' => 'NetzBirthdayReminder',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (\Exception $e) {
        }

        $this->createMailTemplate($connection, $mailTemplateTypeId);
    }

    private function getLanguageIdByLocale(Connection $connection, string $locale): string
    {
        $sql = <<<SQL
select `language`.`id`
from `language`
inner join `locale` on `locale`.`id` = `language`.`locale_id`
where `locale`.`code` = :code
SQL;

        $languageId = $connection->executeQuery($sql, ['code' => $locale])->fetchOne();
        if (!$languageId) {
            throw new \RuntimeException(sprintf('Language for locale "%s" not found.', $locale));
        }

        return $languageId;
    }

    private function createMailTemplate(Connection $connection, string $mailTemplateTypeId): void
    {
        $mailTemplateId = '76aff5086352a181890f44bba27254ea';

        try {
            $connection->insert('mail_template', [
                'id' => Uuid::fromHexToBytes($mailTemplateId),
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'system_default' => 0,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (\Exception $e) {
        }

        try {
            $connection->insert('mail_template_translation', [
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                'language_id' => $this->getLanguageIdByLocale($connection, 'de-DE'),
                'sender_name' => 'getriebemarkt.de',
                'subject' => 'Hundegeburtstage in 14 Tagen',
                'description' => '',
                'content_html' => $this->getContent(),
                'content_plain' => $this->getContent(true),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (\Exception $e) {
        }

        try {
            $connection->insert('mail_template_translation', [
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                'language_id' => $this->getLanguageIdByLocale($connection, 'en-GB'),
                'sender_name' => 'getriebemarkt.de',
                'subject' => 'Dog birthdays in 14 days',
                'description' => '',
                'content_html' => $this->getContent(),
                'content_plain' => $this->getContent(true),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        } catch (\Exception $e) {
        }

        $this->addTemplateToSalesChannels($connection, $mailTemplateTypeId, $mailTemplateId);
    }

    private function getContent(bool $isPlain = false): ?string
    {
        $html = \file_get_contents(__DIR__ . '/../Email/netz_birthday_reminder.html.twig');
        if (!$html) {
            return null;
        }

        if ($isPlain) {
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
