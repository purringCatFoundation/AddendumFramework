<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Auth\AuthRepositoryInterface;
use PCF\Addendum\Repository\User\AdminRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin:grant',
    description: 'Grant admin privileges to a user'
)]
class GrantAdminCommand extends Command
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly AdminRepository $adminRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'User email to grant admin privileges')
            ->addOption('reason', null, InputOption::VALUE_REQUIRED, 'Reason for granting admin privileges')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List active admins')
            ->addOption('stats', null, InputOption::VALUE_NONE, 'Show admin statistics');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Show statistics
        if ($input->getOption('stats')) {
            return $this->showStatistics($io);
        }

        // List active admins
        if ($input->getOption('list')) {
            return $this->listAdmins($io);
        }

        // Grant admin
        $email = $input->getArgument('email');
        if (!$email) {
            $io->error('Email argument is required when granting admin privileges');
            return Command::FAILURE;
        }

        $reason = $input->getOption('reason');

        // Find user by email
        $user = $this->authRepository->findUserByEmail($email);
        if ($user === null) {
            $io->error("User not found: $email");
            return Command::FAILURE;
        }

        $userUuid = $user['uuid'];

        // Check if already admin
        if ($this->adminRepository->isUserAdmin($userUuid)) {
            $io->warning("User $email is already an admin");
            return Command::SUCCESS;
        }

        try {
            $admin = $this->adminRepository->grantAdminPrivileges($userUuid, null, $reason);

            $io->success("Admin privileges granted to $email");
            $io->table(
                ['Property', 'Value'],
                [
                    ['Admin UUID', $admin->uuid],
                    ['User UUID', $userUuid],
                    ['Email', $email],
                    ['Granted At', $admin->grantedAt->format('Y-m-d H:i:s')],
                    ['Reason', $reason ?? 'CLI grant'],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to grant admin privileges: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showStatistics(SymfonyStyle $io): int
    {
        $stats = $this->adminRepository->getStatistics();

        $io->section('Admin Statistics');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Admin Records', $stats['total_admins']],
                ['Active Admins', $stats['active_admins']],
                ['Revoked Admins', $stats['revoked_admins']],
                ['Granted Last 30 Days', $stats['admins_granted_last_30d']],
                ['Revoked Last 30 Days', $stats['admins_revoked_last_30d']],
            ]
        );

        return Command::SUCCESS;
    }

    private function listAdmins(SymfonyStyle $io): int
    {
        $admins = $this->adminRepository->listActiveAdmins();

        if (empty($admins)) {
            $io->info('No active admins found');
            return Command::SUCCESS;
        }

        $io->section('Active Admins');

        $rows = [];
        foreach ($admins as $admin) {
            $rows[] = [
                $admin['admin_uuid'],
                $admin['user_email'],
                $admin['granted_at'],
                $admin['granted_by_email'] ?? 'CLI',
                $admin['granted_reason'] ?? '-',
            ];
        }

        $io->table(
            ['Admin UUID', 'Email', 'Granted At', 'Granted By', 'Reason'],
            $rows
        );

        return Command::SUCCESS;
    }
}
