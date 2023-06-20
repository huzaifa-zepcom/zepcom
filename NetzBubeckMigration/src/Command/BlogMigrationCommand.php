<?php

namespace NetzBubeckMigration\Command;

use NetzBubeckMigration\Service\BlogMigrationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BlogMigrationCommand extends Command
{
    protected static $defaultName = 'netz:migration:blog';

    private BlogMigrationService $blogMigrationService;

    public function __construct(BlogMigrationService $blogMigrationService)
    {
        $this->blogMigrationService = $blogMigrationService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $io = new SymfonyStyle($input, $output);

        $this->blogMigrationService->execute();
        return 0;
    }
}