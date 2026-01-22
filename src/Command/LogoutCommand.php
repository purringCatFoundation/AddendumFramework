<?php

declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Auth\TokenValidationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'auth:logout', description: 'Invalidate JWT tokens to log out users')]
class LogoutCommand extends Command
{

    public function __construct(private TokenValidationRepository $tokenValidationRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('user', InputArgument::OPTIONAL, 'User UUID')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Logout all users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $input->getArgument('user');
        $all = (bool) $input->getOption('all');
        if ($all) {
            $this->tokenValidationRepository->revokeAllTokens('cli_logout_all');
            $output->writeln('All users logged out.');
            return Command::SUCCESS;
        }
        if (is_string($user) && $user !== '') {
            $this->tokenValidationRepository->revokeUserTokens($user, 'cli_logout_user');
            $output->writeln(sprintf('User %s logged out.', $user));
            return Command::SUCCESS;
        }
        $output->writeln('Specify user UUID or use --all option.');
        return Command::FAILURE;
    }
}
