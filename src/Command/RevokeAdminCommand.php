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
    name: 'app:admin:revoke',
    description: 'Revoke admin privileges from a user'
)]
class RevokeAdminCommand extends Command
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
            ->addArgument('email', InputArgument::REQUIRED, 'User email to revoke admin privileges from')
            ->addOption('reason', null, InputOption::VALUE_REQUIRED, 'Reason for revoking admin privileges')
            ->addOption('audit', null, InputOption::VALUE_NONE, 'Show audit trail for user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $reason = $input->getOption('reason');

        // Find user by email
        $user = $this->authRepository->findUserByEmail($email);
        if ($user === null) {
            $io->error("User not found: $email");
            return Command::FAILURE;
        }

        $userUuid = $user['uuid'];

        // Show audit trail
        if ($input->getOption('audit')) {
            return $this->showAuditTrail($io, $userUuid, $email);
        }

        // Check if user is admin
        if (!$this->adminRepository->isUserAdmin($userUuid)) {
            $io->warning("User $email is not an admin");
            return Command::SUCCESS;
        }

        // Revoke admin privileges
        $revoked = $this->adminRepository->revokeAdminPrivileges($userUuid, null, $reason);

        if ($revoked) {
            $io->success("Admin privileges revoked from $email");
        } else {
            $io->error("Failed to revoke admin privileges from $email");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function showAuditTrail(SymfonyStyle $io, string $userUuid, string $email): int
    {
        $trail = $this->adminRepository->getAuditTrail($userUuid);

        if (empty($trail)) {
            $io->info("No admin history found for $email");
            return Command::SUCCESS;
        }

        $io->section("Admin Audit Trail for $email");

        $rows = [];
        foreach ($trail as $entry) {
            $rows[] = [
                $entry['admin_uuid'],
                $entry['action'],
                $entry['action_at'],
                $entry['action_by_email'] ?? 'CLI',
                $entry['reason'] ?? '-',
            ];
        }

        $io->table(
            ['Admin UUID', 'Action', 'Action At', 'Action By', 'Reason'],
            $rows
        );

        return Command::SUCCESS;
    }
}
