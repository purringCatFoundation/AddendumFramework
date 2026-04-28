<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Repository\User\ApplicationTokenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-token',
    description: 'Generate a long-lived application token for external services'
)]
class GenerateApplicationTokenCommand extends Command
{
    public function __construct(
        private readonly ApplicationTokenRepository $tokenRepository,
        private readonly JwtConfig $jwtConfig
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('application-name', InputArgument::REQUIRED, 'Name of the application/service')
            ->addArgument('owner-name', InputArgument::REQUIRED, 'Name of the person/team responsible')
            ->addArgument('owner-email', InputArgument::REQUIRED, 'Email of the person/team responsible');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $applicationName = $input->getArgument('application-name');
        $ownerName = $input->getArgument('owner-name');
        $ownerEmail = $input->getArgument('owner-email');

        // Validate email format
        if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $io->error('Invalid email format: ' . $ownerEmail);
            return Command::FAILURE;
        }

        // Generate JTI (JWT ID)
        $jti = bin2hex(random_bytes(16));

        $issuedAt = time();
        $token = Jwt::encode(new TokenPayload(
            sub: $applicationName,
            exp: $issuedAt + $this->jwtConfig->refreshTokenLifetime,
            jti: $jti,
            iat: $issuedAt,
            tokenType: TokenType::APPLICATION
        ), $this->jwtConfig->secret);

        // Store token hash in database
        $tokenHash = hash('sha256', $token);

        try {
            $appToken = $this->tokenRepository->createToken(
                $tokenHash,
                $applicationName,
                $ownerName,
                $ownerEmail,
                $jti
            );

            $io->success('Application token generated successfully!');
            $io->section('Token Details');
            $io->table(
                ['Property', 'Value'],
                [
                    ['UUID', $appToken->uuid],
                    ['Application', $applicationName],
                    ['Owner', "$ownerName <$ownerEmail>"],
                    ['JTI', $jti],
                    ['Created At', $appToken->createdAt->format('Y-m-d H:i:s')],
                ]
            );

            $io->warning('Save this token securely - it will not be shown again!');
            $io->block($token, null, 'fg=green');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to generate token: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
