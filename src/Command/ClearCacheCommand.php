<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use Symfony\Component\Console\Attribute\AsCommand;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cache:clear', description: 'Clear application cache')]
class ClearCacheCommand extends Command
{
    public function __construct(private CacheInterface $cache)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cache->clear();
        $output->writeln('Cache cleared.');
        return Command::SUCCESS;
    }
}
