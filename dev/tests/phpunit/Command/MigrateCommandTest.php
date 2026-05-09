<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use Ds\Map;
use Ds\Vector;
use PCF\Addendum\Command\MigrateCommand;
use PCF\Addendum\Database\MigrationRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class MigrateCommandTest extends TestCase
{
    public function testReportsDatabaseIsUpToDate(): void
    {
        $runner = $this->createMock(MigrationRunner::class);
        $runner->expects(self::once())->method('pending')->willReturn(new Map());
        $runner->expects(self::never())->method('run');

        $tester = new CommandTester(new MigrateCommand($runner));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Database is up to date.', $tester->getDisplay());
    }

    public function testShowsAndRunsPendingMigrations(): void
    {
        $runner = $this->createMock(MigrationRunner::class);
        $runner->expects(self::once())->method('pending')->willReturn(new Map([
            '001_init.sql' => 'CREATE TABLE example (id INT);',
        ]));
        $runner->expects(self::once())->method('run')->willReturn(new Vector(['001_init.sql']));

        $tester = new CommandTester(new MigrateCommand($runner));
        $tester->execute(['--show' => true, '--run' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Database is not synchronized.', $tester->getDisplay());
        self::assertStringContainsString('001_init.sql:', $tester->getDisplay());
        self::assertStringContainsString('CREATE TABLE example', $tester->getDisplay());
        self::assertStringContainsString('Applied migrations:', $tester->getDisplay());
    }
}
