<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Command\CronCommand;
use PCF\Addendum\Cron\CronDefinition;
use PCF\Addendum\Cron\CronDefinitionCollection;
use PCF\Addendum\Cron\CronService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CronCommandTest extends TestCase
{
    public function testListsCronsByDefault(): void
    {
        $service = $this->createMock(CronService::class);
        $service->expects(self::once())->method('listCrons')->willReturn(new CronDefinitionCollection([
            new CronDefinition('daily', '0 0 * * *', true),
            new CronDefinition('disabled', '*/5 * * * *', false),
        ]));

        $tester = new CommandTester(new CronCommand($service));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('daily\tenabled\t0 0 * * *', $tester->getDisplay());
        self::assertStringContainsString('disabled\tdisabled\t*/5 * * * *', $tester->getDisplay());
    }

    public function testRunsScheduledJobs(): void
    {
        $service = $this->createMock(CronService::class);
        $service->expects(self::once())->method('runScheduled')->with('daily');
        $service->expects(self::once())->method('scheduleJobs')->with('daily');

        $tester = new CommandTester(new CronCommand($service));
        $tester->execute(['--run' => 'daily']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testEnablesCronAndSchedulesJobs(): void
    {
        $service = $this->createMock(CronService::class);
        $service->expects(self::once())->method('enable')->with('daily');
        $service->expects(self::once())->method('scheduleJobs')->with('daily');

        $tester = new CommandTester(new CronCommand($service));
        $tester->execute(['--enable' => 'daily']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testDisablesCron(): void
    {
        $service = $this->createMock(CronService::class);
        $service->expects(self::once())->method('disable')->with('daily');
        $service->expects(self::never())->method('scheduleJobs');

        $tester = new CommandTester(new CronCommand($service));
        $tester->execute(['--disable' => 'daily']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testSetRequiresExpression(): void
    {
        $service = $this->createMock(CronService::class);
        $service->expects(self::never())->method('updateExpression');

        $tester = new CommandTester(new CronCommand($service));
        $tester->execute(['--set' => 'daily']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Expression is required when using --set', $tester->getDisplay());
    }

    public function testSetsExpressionAndSchedulesJobs(): void
    {
        $service = $this->createMock(CronService::class);
        $service->expects(self::once())->method('updateExpression')->with('daily', '*/10 * * * *');
        $service->expects(self::once())->method('scheduleJobs')->with('daily');

        $tester = new CommandTester(new CronCommand($service));
        $tester->execute(['--set' => 'daily', '--expression' => '*/10 * * * *']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }
}
