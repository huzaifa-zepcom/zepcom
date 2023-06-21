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
        $invoices = []; // Initialize an empty array to store invoice file paths
        $folder = $this->getImportFolder(); // Get the import folder path

        $directory_iterator = new RecursiveDirectoryIterator($folder);
        $iterator = new RecursiveIteratorIterator($directory_iterator);
        $regex_iterator = new RegexIterator($iterator, self::INVOICE_PATTERN);
        $regex_iterator->setFlags(RegexIterator::USE_KEY);

        foreach ($regex_iterator as $file) {
            $invoices[] = $file->getPathname(); // Add the file's pathname to the invoices array
        }

        return $invoices; // Return the array of invoice file paths
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
        $count = 0; // Initialize a counter variable
        $this->connection->beginTransaction(); // Start a database transaction
        $this->connection->executeStatement('TRUNCATE TABLE `kit_invoice`'); // Truncate the "kit_invoice" table

        try {
            $allInvoice = $this->getAllInvoices(); // Get all invoice file paths
            foreach ($allInvoice as $invoice) {
                preg_match(self::INVOICE_PATTERN, $invoice, $matches); // Match the invoice pattern against the file path
                $matchedInvoice = $matches[0] ?? null; // Extract the matched invoice number

                if ($matchedInvoice) {
                    $orderInvoice = explode('.', $matchedInvoice); // Split the invoice number and extension
                    if (isset($orderInvoice[0])) {
                        // Insert a new row in the "kit_invoice" table with relevant data
                        $this->connection->insert('kit_invoice', [
                            'id' => Uuid::fromHexToBytes(md5(basename($invoice))),
                            'order_number' => $orderInvoice[0],
                            'file_name' => $invoice
                        ]);

                        $count++; // Increment the counter
                    }
                }
            }

            $this->connection->commit(); // Commit the transaction
        } catch (\Exception $e) {
            $count = 0; // Reset the counter to 0
            $this->connection->rollBack(); // Rollback the transaction
        }

        return $count; // Return the count of inserted rows
    }
}
