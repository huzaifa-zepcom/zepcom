<?php

declare(strict_types=1);

namespace KitInvoiceViewer\Service;

use Doctrine\DBAL\Connection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use SplFileInfo;

class KitInvoiceService
{
    private const INVOICE_PATTERN = '/[^_]+\.[^.]+$/';

    /**
     * @var SystemConfigService
     */
    private $configService;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        SystemConfigService $configService,
        Connection $connection
    ) {
        $this->configService = $configService;
        $this->connection = $connection;
    }

    private function getAllInvoices(): array
    {
        $invoices = [];
        $folder = $this->getImportFolder();
        $directory_iterator = new RecursiveDirectoryIterator($folder);
        $iterator = new RecursiveIteratorIterator($directory_iterator);
        $regex_iterator = new RegexIterator($iterator, self::INVOICE_PATTERN);
        $regex_iterator->setFlags(RegexIterator::USE_KEY);
        /** @var SplFileInfo $file */
        foreach ($regex_iterator as $file) {
            $invoices[] = $file->getPathname();
        }

        return $invoices;
    }

    public function getInvoicesForOrder(string $orderNumber): array
    {
        $sql = <<<SQL
SELECT *, LOWER(HEX(`id`)) AS `id` FROM `kit_invoice` WHERE order_number = ?
SQL;

        return $this->connection->fetchAll($sql, [$orderNumber]);
    }

    public function getInvoiceFromHash(string $hash): array
    {
        $sql = <<<SQL
SELECT *, LOWER(HEX(`id`)) AS `id` FROM `kit_invoice` WHERE `id` = UNHEX(?) LIMIT 1
SQL;

        $invoice = $this->connection->fetchAssoc($sql, [$hash]);

        return \is_array($invoice) ? $invoice : [];
    }

    private function getImportFolder(): string
    {
        $folder = $this->configService->get('KitInvoiceViewer.config.invoiceFolder');

        return dirname(__DIR__, 5) . '/' . $folder;
    }

    public function generateMapping(): int
    {
        $count = 0;
        $this->connection->beginTransaction();
        $this->connection->executeStatement('TRUNCATE TABLE `kit_invoice`');
        try {
            $allInvoice = $this->getAllInvoices();
            foreach ($allInvoice as $invoice) {
                preg_match(self::INVOICE_PATTERN, $invoice, $matches);
                $matchedInvoice = $matches[0] ?? null;
                if ($matchedInvoice) {
                    $orderInvoice = explode('.', $matchedInvoice);
                    if (isset($orderInvoice[0])) {
                        $this->connection->insert('kit_invoice', [
                            'id' => Uuid::fromHexToBytes(md5(basename($invoice))),
                            'order_number' => $orderInvoice[0],
                            'file_name' => $invoice
                        ]);
                        $count++;
                    }
                }
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $count = 0;
            $this->connection->rollBack();
        }

        return $count;
    }
}
