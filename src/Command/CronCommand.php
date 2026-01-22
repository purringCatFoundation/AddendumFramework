<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Cron\CronService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cron:run', description: 'Cron job management')]
class CronCommand extends Command
{
    public function __construct(private CronService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('run', null, InputOption::VALUE_OPTIONAL, 'Run scheduled jobs, optionally only for a specific cron code', false)
            ->addOption('enable', null, InputOption::VALUE_REQUIRED, 'Enable a cron code')
            ->addOption('disable', null, InputOption::VALUE_REQUIRED, 'Disable a cron code')
            ->addOption('set', null, InputOption::VALUE_REQUIRED, 'Change cron frequency for code (requires --expression)')
            ->addOption('expression', null, InputOption::VALUE_REQUIRED, 'Cron expression used with --set');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (($code = $input->getOption('enable')) !== null) {
            $this->service->enable($code);
            $this->service->scheduleJobs($code);
            return Command::SUCCESS;
        }

        if (($code = $input->getOption('disable')) !== null) {
            $this->service->disable($code);
            return Command::SUCCESS;
        }

        if (($code = $input->getOption('set')) !== null) {
            $expr = $input->getOption('expression');
            if ($expr === null) {
                $output->writeln('Expression is required when using --set');
                return Command::FAILURE;
            }
            $this->service->updateExpression($code, (string) $expr);
            $this->service->scheduleJobs($code);
            return Command::SUCCESS;
        }

        $run = $input->getOption('run');
        if ($run !== false) {
            $this->service->runScheduled($run ?: null);
            $this->service->scheduleJobs($run ?: null);
            return Command::SUCCESS;
        }

        foreach ($this->service->listCrons() as $cron) {
            $output->writeln(sprintf('%s\t%s\t%s', $cron['code'], $cron['enabled'] ? 'enabled' : 'disabled', $cron['expression']));
        }
        return Command::SUCCESS;
    }
}
