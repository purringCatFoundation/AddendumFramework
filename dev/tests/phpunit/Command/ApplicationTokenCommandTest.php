<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Command\GenerateApplicationTokenCommand;
use PCF\Addendum\Command\RevokeApplicationTokensCommand;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Repository\User\ApplicationTokenRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ApplicationTokenCommandTest extends TestCase
{
    public function testGenerateApplicationTokenFailsForInvalidOwnerEmail(): void
    {
        $tester = new CommandTester(new GenerateApplicationTokenCommand(
            $this->tokenRepository(),
            new JwtConfig('test-secret', 60, 120)
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
        $tester = new CommandTester(new RevokeApplicationTokensCommand($this->tokenRepository()));

        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('You must specify at least one filter', $tester->getDisplay());
    }

    public function testRevokeApplicationTokensRejectsInvalidDate(): void
    {
        $tester = new CommandTester(new RevokeApplicationTokensCommand($this->tokenRepository()));

        $tester->execute(['--after' => 'not-a-date']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid date format. Use YYYY-MM-DD', $tester->getDisplay());
    }

    private function tokenRepository(): ApplicationTokenRepository
    {
        return new ApplicationTokenRepository(new PDO('sqlite::memory:'));
    }
}
