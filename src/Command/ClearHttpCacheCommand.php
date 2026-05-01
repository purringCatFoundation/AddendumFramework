<?php

declare(strict_types=1);

namespace PCF\Addendum\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'http-cache:clear', description: 'Clear HTTP cache')]
class ClearHttpCacheCommand extends Command
{
    public function __construct(private string $url)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'PURGE',
                'ignore_errors' => true,
            ],
        ]);
        @file_get_contents($this->url, false, $context);
        $output->writeln('HTTP cache clear request sent.');
        return Command::SUCCESS;
    }
}
