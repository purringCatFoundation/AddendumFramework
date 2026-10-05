<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Auth\ApplicationTokenValidator;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Command\GenerateApplicationTokenCommand;
use PCF\Addendum\Command\RevokeApplicationTokensCommand;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Repository\User\ApplicationTokenRepository;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ApplicationTokenCommandTest extends TestCase
{
    public function testGenerateApplicationTokenFailsForInvalidOwnerEmail(): void
    {
        $keyPair = JwtKeyPair::shared();

        $tester = new CommandTester(new GenerateApplicationTokenCommand(
            $this->tokenRepository(),
            new JwtConfig($keyPair->privateKeyPath, $keyPair->publicKeyPath, $keyPair->privateKeyPassphrase, 60, 120),
            $this->createMock(ApplicationTokenValidator::class)
        ));

        $tester->execute([
            'application-name' => 'external-api',
            'owner-name' => 'Team',
            'owner-email' => 'not-an-email',
        ]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid email format: not-an-email', $tester->getDisplay());
    }

    public function testRevokeApplicationTokensRequiresAtLeastOneFilter(): void
    {
        $tester = new CommandTester(new RevokeApplicationTokensCommand($this->createMock(TokenValidationRepository::class)));

        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('You must specify --before', $tester->getDisplay());
    }

    public function testRevokeApplicationTokensRejectsInvalidDate(): void
    {
        $tester = new CommandTester(new RevokeApplicationTokensCommand($this->createMock(TokenValidationRepository::class)));

        $tester->execute(['--before' => 'not-a-date']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid date format. Use YYYY-MM-DD HH:MM:SS or Unix timestamp', $tester->getDisplay());
    }

    public function testRevokeApplicationTokensCreatesRevocationRule(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::once())
            ->method('revokeTokensBefore')
            ->with(
                TokenType::APPLICATION,
                'external-api',
                self::callback(static fn(\DateTimeImmutable $before): bool => $before->format('Y-m-d H:i:s') === '2026-05-17 15:22:17'),
                'jti-1',
                'rotation'
            );
        $tester = new CommandTester(new RevokeApplicationTokensCommand($repository));

        $tester->execute([
            '--uuid' => 'external-api',
            '--jti' => 'jti-1',
            '--before' => '2026-05-17 15:22:17',
            '--reason' => 'rotation',
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Token revocation rule created', $tester->getDisplay());
    }

    public function testRevokeApplicationTokensAcceptsUnixTimestamp(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::once())
            ->method('revokeTokensBefore')
            ->with(
                TokenType::APPLICATION,
                null,
                self::callback(static fn(\DateTimeImmutable $before): bool => $before->getTimestamp() === 1_779_031_337),
                null,
                'application_token_revocation'
            );
        $tester = new CommandTester(new RevokeApplicationTokensCommand($repository));

        $tester->execute(['--before' => '1779031337']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Token revocation rule created', $tester->getDisplay());
    }

    private function tokenRepository(): ApplicationTokenRepository
    {
        return new ApplicationTokenRepository(new PDO('sqlite::memory:'));
    }
}
