<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Command\LogoutCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class LogoutCommandTest extends TestCase
{
    public function testLogsOutAllUsers(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::once())
            ->method('revokeAllTokens')
            ->with('cli_logout_all');
        $repository->expects(self::never())->method('revokeUserTokens');

        $tester = new CommandTester(new LogoutCommand($repository));
        $tester->execute(['--all' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('All users logged out.', $tester->getDisplay());
    }

    public function testLogsOutSingleUser(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::once())
            ->method('revokeUserTokens')
            ->with('user-1', 'cli_logout_user');
        $repository->expects(self::never())->method('revokeAllTokens');

        $tester = new CommandTester(new LogoutCommand($repository));
        $tester->execute(['user' => 'user-1']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('User user-1 logged out.', $tester->getDisplay());
    }

    public function testFailsWithoutUserOrAllOption(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::never())->method('revokeUserTokens');
        $repository->expects(self::never())->method('revokeAllTokens');

        $tester = new CommandTester(new LogoutCommand($repository));
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Specify user UUID or use --all option.', $tester->getDisplay());
    }
}
