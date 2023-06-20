<?php

namespace NetzBubeckMigration\Command;

use NetzBubeckMigration\Service\BlogMigrationService;
use NetzBubeckMigration\Service\StoreLocatorMigrationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StoreLocatorMigrationCommand extends Command
{
    protected static $defaultName = 'netz:migration:storelocator';
    private StoreLocatorMigrationService $migrationService;

    public function __construct(StoreLocatorMigrationService $migrationService)
    {
        parent::__construct();
        $this->migrationService = $migrationService;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $io = new SymfonyStyle($input, $output);

        $this->migrationService->execute();

        return 0;
    }
}