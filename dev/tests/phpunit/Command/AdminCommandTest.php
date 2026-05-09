<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Auth\AuthRepositoryInterface;
use PCF\Addendum\Command\GrantAdminCommand;
use PCF\Addendum\Command\RevokeAdminCommand;
use PCF\Addendum\Repository\User\AdminRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AdminCommandTest extends TestCase
{
    public function testGrantAdminRequiresEmailArgument(): void
    {
        $authRepository = $this->createMock(AuthRepositoryInterface::class);
        $authRepository->expects(self::never())->method('findUserByEmail');

        $tester = new CommandTester(new GrantAdminCommand($authRepository, $this->adminRepository()));
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Email argument is required', $tester->getDisplay());
    }

    public function testGrantAdminFailsWhenUserIsMissing(): void
    {
        $authRepository = $this->createMock(AuthRepositoryInterface::class);
        $authRepository->expects(self::once())
            ->method('findUserByEmail')
            ->with('missing@example.com')
            ->willReturn(null);

        $tester = new CommandTester(new GrantAdminCommand($authRepository, $this->adminRepository()));
        $tester->execute(['email' => 'missing@example.com']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('User not found: missing@example.com', $tester->getDisplay());
    }

    public function testRevokeAdminFailsWhenUserIsMissing(): void
    {
        $authRepository = $this->createMock(AuthRepositoryInterface::class);
        $authRepository->expects(self::once())
            ->method('findUserByEmail')
            ->with('missing@example.com')
            ->willReturn(null);

        $tester = new CommandTester(new RevokeAdminCommand($authRepository, $this->adminRepository()));
        $tester->execute(['email' => 'missing@example.com']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('User not found: missing@example.com', $tester->getDisplay());
    }

    private function adminRepository(): AdminRepository
    {
        return new AdminRepository(new PDO('sqlite::memory:'));
    }
}
