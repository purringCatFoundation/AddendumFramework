<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use DateTimeImmutable;
use PCF\Addendum\Repository\User\ApplicationTokenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:revoke-tokens',
    description: 'Revoke application tokens by various criteria'
)]
class RevokeApplicationTokensCommand extends Command
{
    public function __construct(
        private readonly ApplicationTokenRepository $tokenRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('application', null, InputOption::VALUE_REQUIRED, 'Revoke tokens for specific application name')
            ->addOption('owner', null, InputOption::VALUE_REQUIRED, 'Revoke tokens for specific owner email')
            ->addOption('after', null, InputOption::VALUE_REQUIRED, 'Revoke tokens created after date (YYYY-MM-DD)')
            ->addOption('reason', null, InputOption::VALUE_REQUIRED, 'Reason for revocation')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List active tokens instead of revoking')
            ->addOption('stats', null, InputOption::VALUE_NONE, 'Show token statistics');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Show statistics
        if ($input->getOption('stats')) {
            return $this->showStatistics($io);
        }

        // List active tokens
        if ($input->getOption('list')) {
            return $this->listTokens($io);
        }

        // Revoke tokens
        $application = $input->getOption('application');
        $owner = $input->getOption('owner');
        $afterStr = $input->getOption('after');
        $reason = $input->getOption('reason');

        if (!$application && !$owner && !$afterStr) {
            $io->error('You must specify at least one filter: --application, --owner, or --after');
            return Command::FAILURE;
        }

        $after = null;
        if ($afterStr) {
            try {
                $after = new DateTimeImmutable($afterStr);
            } catch (\Exception $e) {
                $io->error('Invalid date format. Use YYYY-MM-DD');
                return Command::FAILURE;
            }
        }

        $revokedCount = 0;

        if ($application && !$owner && !$after) {
            $revokedCount = $this->tokenRepository->revokeByApplicationName($application, null, $reason);
        } elseif ($owner && !$application && !$after) {
            $revokedCount = $this->tokenRepository->revokeByOwner($owner, null, $reason);
        } elseif ($after) {
            $revokedCount = $this->tokenRepository->revokeByDate($after, $application, $owner, $reason);
        } else {
            // Combined filters - use date-based with additional filters
            if ($after) {
                $revokedCount = $this->tokenRepository->revokeByDate($after, $application, $owner, $reason);
            } elseif ($application) {
                $revokedCount = $this->tokenRepository->revokeByApplicationName($application, null, $reason);
            }
        }

        if ($revokedCount > 0) {
            $io->success("Revoked $revokedCount token(s)");
        } else {
            $io->warning('No tokens matched the criteria');
        }

        return Command::SUCCESS;
    }

    private function showStatistics(SymfonyStyle $io): int
    {
        $stats = $this->tokenRepository->getStatistics();

        $io->section('Application Token Statistics');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Tokens', $stats->totalTokens],
                ['Active Tokens', $stats->activeTokens],
                ['Revoked Tokens', $stats->revokedTokens],
                ['Unique Applications', $stats->uniqueApplications],
                ['Unique Owners', $stats->uniqueOwners],
                ['Used Last 24h', $stats->tokensUsedLast24Hours],
                ['Used Last 7d', $stats->tokensUsedLast7Days],
            ]
        );

        return Command::SUCCESS;
    }

    private function listTokens(SymfonyStyle $io): int
    {
        $tokens = $this->tokenRepository->listActiveTokens();

        if ($tokens->isEmpty()) {
            $io->info('No active application tokens found');
            return Command::SUCCESS;
        }

        $io->section('Active Application Tokens');

        $rows = [];
        foreach ($tokens as $token) {
            $rows[] = [
                $token->uuid,
                $token->applicationName,
                $token->ownerName,
                $token->ownerEmail,
                $token->createdAt->format('Y-m-d H:i'),
                $token->lastUsedAt?->format('Y-m-d H:i') ?? 'Never',
            ];
        }

        $io->table(
            ['UUID', 'Application', 'Owner', 'Email', 'Created', 'Last Used'],
            $rows
        );

        return Command::SUCCESS;
    }
}
