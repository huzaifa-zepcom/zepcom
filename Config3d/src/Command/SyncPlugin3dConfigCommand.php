<?php declare(strict_types=1);

namespace Config3d\Command;

use Config3d\Service\Config3dPluginService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SyncPlugin3dConfigCommand extends Command
{
    protected static $defaultName = 'plugin3d:sync';

    private Config3dPluginService $plugin3dService;

    public function __construct(Config3dPluginService $plugin3dService)
    {
        parent::__construct();
        $this->plugin3dService = $plugin3dService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Starting sync to plugin3d service');

        $this->plugin3dService->sync($io);

        return 0;
    }
}