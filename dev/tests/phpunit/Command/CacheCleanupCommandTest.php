<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\ApplicationCacheMode;
use PCF\Addendum\Application\Cache\CompiledCacheCleaner;
use PCF\Addendum\Command\CacheCleanupCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CacheCleanupCommandTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/addendum-cache-cleanup-' . uniqid('', true);
        mkdir($this->cacheDirectory, 0775, true);
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
    }

    public function testRemovesCompiledCacheFiles(): void
    {
        foreach (['routes.php', 'metadata.php', 'app.php'] as $fileName) {
            file_put_contents($this->cacheDirectory . '/' . $fileName, '<?php return null;');
        }

        $tester = new CommandTester($this->command());
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileDoesNotExist($this->cacheDirectory . '/routes.php');
        self::assertFileDoesNotExist($this->cacheDirectory . '/metadata.php');
        self::assertFileDoesNotExist($this->cacheDirectory . '/app.php');
        self::assertStringContainsString('Removed ' . $this->cacheDirectory . '/routes.php', $tester->getDisplay());
    }

    public function testSucceedsWhenNoCompiledCacheFilesExist(): void
    {
        $tester = new CommandTester($this->command());
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No compiled cache files found.', $tester->getDisplay());
    }

    private function command(): CacheCleanupCommand
    {
        return new CacheCleanupCommand(
            new ApplicationCacheConfiguration(ApplicationCacheMode::AUTO, 'prod', $this->cacheDirectory),
            new CompiledCacheCleaner()
        );
    }
}
