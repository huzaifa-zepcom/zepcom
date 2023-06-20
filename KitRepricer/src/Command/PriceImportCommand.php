<?php

declare(strict_types=1);

namespace KitAutoPriceUpdate\Command;

use Exception;
use KitAutoPriceUpdate\Components\KitAutoPriceImportService;
use KitAutoPriceUpdate\Components\KitAutoPriceService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PriceImportCommand extends Command
{
    protected static $defaultName = 'kit:price';

    /**
     * @var KitAutoPriceImportService
     */
    private $kitAutoPriceImport;

    /**
     * @var KitAutoPriceService
     */
    private $autoPriceService;

    public function __construct(KitAutoPriceImportService $kitAutoPriceImport, KitAutoPriceService $autoPriceService)
    {
        parent::__construct(self::$defaultName);
        $this->kitAutoPriceImport = $kitAutoPriceImport;
        $this->autoPriceService = $autoPriceService;
    }

    protected function configure(): void
    {
        $this->addOption('import', 'i', InputOption::VALUE_NONE, 'Execute Geizhals price import');
        $this->addOption('sink', 's', InputOption::VALUE_NONE, 'Execute sink rules');
        $this->addOption('raise', 'r', InputOption::VALUE_NONE, 'Execute raise rules');
        $this->addOption(
            'test',
            't',
            InputOption::VALUE_NONE,
            'Execute sink/raise rules in Test Mode. You will see the entries in the log, ' .
            'but it wont be saved in the product'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $mainStarted = time();
            $output->writeln('======================== START =======================');
            $output->writeln('Started at: ' . date('d.m.Y H:i:s', $mainStarted));
            if ($input->getOption('import')) {
                $started = time();
                $output->writeln('Executing geizhal import at ' . date('H:i:s'));
                $this->kitAutoPriceImport->execute();
                $this->executionTime($output, $started);
            }
            $exceptionIds = [];
            if ($input->getOption('sink')) {
                $started = time();
                $output->writeln('Executing sink price comparison at ' . date('H:i:s'));
                $exceptionIds = $this->autoPriceService->execute('sink', $input->getOption('test'), $exceptionIds);
                $this->executionTime($output, $started);
            }
            if ($input->getOption('raise')) {
                $started = time();
                $output->writeln('Executing raise price comparison at ' . date('H:i:s'));
                $this->autoPriceService->execute('raise', $input->getOption('test'), $exceptionIds);
                $this->executionTime($output, $started);
            }

            $output->writeln('====================== END =======================');
            $this->executionTime($output, $mainStarted);

            return 0;
        } catch (Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * @param OutputInterface $output
     * @param int $started
     */
    private function executionTime(OutputInterface $output, int $started): void
    {
        $output->writeln('Finished at: ' . date('d.m.Y H:i:s'));
        $finished = time();
        $diff = $finished - $started;
        $time = 'seconds';
        if ($diff > 60) {
            $diff /= 60;
            $time = 'minutes';
        }

        $output->writeln(sprintf('Execution time: %s %s', number_format($diff, 2), $time));
    }
}
