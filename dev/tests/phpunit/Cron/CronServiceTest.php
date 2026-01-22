<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Cron;

use PCF\Addendum\Cron\CronService;
use PCF\Addendum\Cron\ScheduleResource;
use PCF\Addendum\Util\FinderFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

final class CronServiceTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        // Create a dedicated test directory for the finder
        $this->testDir = __DIR__ . '/fixtures';
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    }

    public function testRunScheduledMarksCronAsFailedWhenClassNotFound(): void
    {
        // Mock ScheduleResource
        $resource = $this->createMock(ScheduleResource::class);
        $resource->method('cronExists')->willReturn(true);
        $resource->method('getCrons')->willReturn([
            ['code' => 'example', 'expression' => '* * * * *', 'enabled' => true],
        ]);

        $resource->expects($this->once())->method('getScheduled')
            ->with(null)->willReturn([['id' => 1, 'code' => 'example']]);

        // When cron class is not found, markFailed should be called
        $resource->expects($this->once())->method('markFailed')->with(1);
        $resource->expects($this->never())->method('markStarted');
        $resource->expects($this->never())->method('markCompleted');

        // Mock FinderFactory to return finder for empty directory
        $finderFactory = $this->createMock(FinderFactory::class);
        $finder = new Finder();
        $finder->files()->in($this->testDir)->name('*.php');
        $finderFactory->method('create')->willReturn($finder);

        $service = new CronService($resource, $finderFactory);
        $service->runScheduled();
    }

    public function testScheduleJobsDoesNothingWhenNoCronsConfigured(): void
    {
        $resource = $this->createMock(ScheduleResource::class);
        $resource->method('cronExists')->willReturn(false);
        $resource->method('getCrons')->willReturn([]);

        // No crons to schedule, these methods should never be called
        $resource->expects($this->never())->method('isScheduled');
        $resource->expects($this->never())->method('createSchedule');

        // Mock FinderFactory to return finder for empty directory
        $finderFactory = $this->createMock(FinderFactory::class);
        $finder = new Finder();
        $finder->files()->in($this->testDir)->name('*.php');
        $finderFactory->method('create')->willReturn($finder);

        $service = new CronService($resource, $finderFactory);
        $service->scheduleJobs();
    }

    public function testListCronsReturnsCronList(): void
    {
        $expectedCrons = [
            ['code' => 'example', 'expression' => '* * * * *', 'enabled' => true],
        ];

        $resource = $this->createMock(ScheduleResource::class);
        $resource->method('cronExists')->willReturn(true);
        $resource->method('getCrons')->willReturn($expectedCrons);

        // Mock FinderFactory to return finder for empty directory
        $finderFactory = $this->createMock(FinderFactory::class);
        $finder = new Finder();
        $finder->files()->in($this->testDir)->name('*.php');
        $finderFactory->method('create')->willReturn($finder);

        $service = new CronService($resource, $finderFactory);
        $result = $service->listCrons();

        $this->assertSame($expectedCrons, $result);
    }

    public function testEnableCallsResourceEnable(): void
    {
        $resource = $this->createMock(ScheduleResource::class);
        $resource->expects($this->once())->method('enable')->with('example');

        $finderFactory = $this->createMock(FinderFactory::class);

        $service = new CronService($resource, $finderFactory);
        $service->enable('example');
    }

    public function testDisableCallsResourceDisable(): void
    {
        $resource = $this->createMock(ScheduleResource::class);
        $resource->expects($this->once())->method('disable')->with('example');

        $finderFactory = $this->createMock(FinderFactory::class);

        $service = new CronService($resource, $finderFactory);
        $service->disable('example');
    }

    public function testUpdateExpressionCallsResourceSetExpression(): void
    {
        $resource = $this->createMock(ScheduleResource::class);
        $resource->expects($this->once())
            ->method('setExpression')
            ->with('example', '0 * * * *');

        $finderFactory = $this->createMock(FinderFactory::class);

        $service = new CronService($resource, $finderFactory);
        $service->updateExpression('example', '0 * * * *');
    }
}
