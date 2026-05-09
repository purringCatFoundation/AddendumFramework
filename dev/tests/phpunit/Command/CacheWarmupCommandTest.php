<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\ApplicationCacheMode;
use PCF\Addendum\Command\CacheWarmupCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CacheWarmupCommandTest extends TestCase
{
    private string $cacheDirectory;
    private string $actionDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/addendum-cache-warmup-' . uniqid('', true);
        $this->actionDirectory = sys_get_temp_dir() . '/addendum-cache-warmup-actions-' . uniqid('', true);
        mkdir($this->cacheDirectory, 0775, true);
        mkdir($this->actionDirectory, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (['routes.php', 'metadata.php', 'app.php'] as $fileName) {
            $filePath = $this->cacheDirectory . '/' . $fileName;

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        if (is_dir($this->cacheDirectory)) {
            rmdir($this->cacheDirectory);
        }

        if (is_dir($this->actionDirectory)) {
            rmdir($this->actionDirectory);
        }
    }

    public function testWarmupWritesRouteMetadataAndAppCache(): void
    {
        $configuration = new ApplicationCacheConfiguration(ApplicationCacheMode::AUTO, 'prod', $this->cacheDirectory);
        $tester = new CommandTester(new CacheWarmupCommand($configuration));

        $tester->execute(['--action-path' => ['', $this->actionDirectory]]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileExists($configuration->routesFile());
        self::assertFileExists($configuration->metadataFile());
        self::assertFileExists($configuration->appFile());
        self::assertSame(0, (require $configuration->metadataFile())['routeCount']);
        self::assertStringContainsString('Compiled HTTP routes: 0', $tester->getDisplay());
        self::assertStringContainsString('Routes cache: ' . $configuration->routesFile(), $tester->getDisplay());
        self::assertStringContainsString('App cache: ' . $configuration->appFile(), $tester->getDisplay());
    }
}
