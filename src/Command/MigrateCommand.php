<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Database\MigrationRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:migrate', description: 'Run database migrations')]
class MigrateCommand extends Command
{

    public function __construct(private MigrationRunner $runner)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('show', null, InputOption::VALUE_NONE, 'Show SQL queries to be executed')
            ->addOption('run', null, InputOption::VALUE_NONE, 'Execute pending migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pending = $this->runner->pending();

        if (empty($pending)) {
            $output->writeln('Database is up to date.');
            return Command::SUCCESS;
        }

        $output->writeln('Database is not synchronized.');

        if ($input->getOption('show')) {
            foreach ($pending as $name => $sql) {
                $output->writeln("$name:\n$sql");
            }
        }

        if ($input->getOption('run')) {
            $executed = $this->runner->run();
            $output->writeln("Applied migrations:\n" . implode("\n", $executed));
        }

        return Command::SUCCESS;
    }
}
