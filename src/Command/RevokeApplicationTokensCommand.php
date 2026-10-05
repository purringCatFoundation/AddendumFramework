<?php

declare(strict_types=1);

namespace PCF\Addendum\Command;

use DateTimeImmutable;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:revoke-tokens',
    description: 'Create token revocation rules by type, subject, JTI and issue timestamp cutoff'
)]
class RevokeApplicationTokensCommand extends Command
{
    public function __construct(
        private readonly TokenValidationRepository $tokenValidationRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Token type to revoke', TokenType::APPLICATION)
            ->addOption(
                'uuid',
                null,
                InputOption::VALUE_REQUIRED,
                'JWT subject to revoke, e.g. user UUID or application name'
            )
            ->addOption('jti', null, InputOption::VALUE_REQUIRED, 'Revoke one specific JWT ID')
            ->addOption('before', null, InputOption::VALUE_REQUIRED, 'Revoke tokens issued at or before this timestamp')
            ->addOption(
                'reason',
                null,
                InputOption::VALUE_REQUIRED,
                'Reason for revocation',
                'application_token_revocation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tokenType = trim((string) $input->getOption('type'));
        $subject = $input->getOption('uuid');
        $jti = $input->getOption('jti');
        $beforeStr = $input->getOption('before');
        $reason = (string) $input->getOption('reason');

        if ($tokenType === '') {
            $io->error('You must specify --type');
            return Command::FAILURE;
        }

        if (!is_string($beforeStr) || trim($beforeStr) === '') {
            $io->error('You must specify --before');
            return Command::FAILURE;
        }

        $before = $this->parseBefore($beforeStr);
        if ($before === null) {
            $io->error('Invalid date format. Use YYYY-MM-DD HH:MM:SS or Unix timestamp');
            return Command::FAILURE;
        }

        $this->tokenValidationRepository->revokeTokensBefore(
            tokenType: $tokenType,
            subject: is_string($subject) && trim($subject) !== '' ? $subject : null,
            revokedBefore: $before,
            jti: is_string($jti) && trim($jti) !== '' ? $jti : null,
            reason: $reason !== '' ? $reason : 'application_token_revocation'
        );

        $io->success('Token revocation rule created');

        return Command::SUCCESS;
    }

    private function parseBefore(string $value): ?DateTimeImmutable
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            if (ctype_digit($value)) {
                return new DateTimeImmutable('@' . $value);
            }

            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
