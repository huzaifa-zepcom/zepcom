<?php

declare(strict_types=1);

namespace KitInvoiceViewer\Command;

use Exception;
use KitInvoiceViewer\Service\KitInvoiceService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KitInvoiceCommand extends Command
{
    protected static $defaultName = 'kit:invoice';

    /**
     * @var KitInvoiceService
     */
    private $service;

    public function __construct(KitInvoiceService $service)
    {
        parent::__construct(self::$defaultName);
        $this->service = $service;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('=========================================================');
            $output->writeln('Started at: ' . date('d.m.Y H:i:s'));
            $total = $this->service->generateMapping();
            $output->writeln('Generated mapping for (' . $total . ') invoice');
            $output->writeln('Finished at: ' . date('d.m.Y H:i:s'));
            $output->writeln('=========================================================');

            return 0;
        } catch (Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
