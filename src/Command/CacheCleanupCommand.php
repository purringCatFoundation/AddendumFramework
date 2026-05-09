<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\CompiledCacheCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cache:cleanup', description: 'Remove compiled HTTP application cache')]
final class CacheCleanupCommand extends Command
{
    public function __construct(
        private readonly ApplicationCacheConfiguration $configuration,
        private readonly CompiledCacheCleaner $cleaner
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $removed = $this->cleaner->cleanup($this->configuration);

        if ($removed === []) {
            $output->writeln('No compiled cache files found.');

            return Command::SUCCESS;
        }

        foreach ($removed as $filePath) {
            $output->writeln(sprintf('Removed %s', $filePath));
        }

        return Command::SUCCESS;
    }
}
