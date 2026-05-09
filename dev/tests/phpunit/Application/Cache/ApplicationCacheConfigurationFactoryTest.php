<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Application\Cache;

use PCF\Addendum\Application\Cache\ApplicationCacheConfigurationFactory;
use PCF\Addendum\Application\Cache\ApplicationCacheMode;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PHPUnit\Framework\TestCase;

final class ApplicationCacheConfigurationFactoryTest extends TestCase
{
    public function testResolvesRelativeDirectoryAgainstBaseDirectory(): void
    {
        $configuration = new ApplicationCacheConfigurationFactory(new ApplicationCacheTestEnvironmentProvider([
            'APP_CACHE' => 'required',
            'APP_ENV' => 'DEV',
            'APP_CACHE_DIR' => 'cache/addendum/',
        ]))->create('/tmp/project/');

        self::assertSame(ApplicationCacheMode::REQUIRED, $configuration->mode);
        self::assertSame('dev', $configuration->environment);
        self::assertSame('/tmp/project/cache/addendum', $configuration->compiledDirectory);
        self::assertSame('/tmp/project/cache/addendum/routes.php', $configuration->routesFile());
        self::assertSame('/tmp/project/cache/addendum/metadata.php', $configuration->metadataFile());
        self::assertSame('/tmp/project/cache/addendum/app.php', $configuration->appFile());
    }

    public function testPreservesAbsoluteDirectoryAndTrimsTrailingSlash(): void
    {
        $directory = sys_get_temp_dir() . '/addendum-compiled/';
        $configuration = new ApplicationCacheConfigurationFactory(new ApplicationCacheTestEnvironmentProvider([
            'APP_CACHE_DIR' => $directory,
        ]))->create('/tmp/project');

        self::assertSame(rtrim($directory, '/'), $configuration->compiledDirectory);
    }

    public function testInvalidModeFallsBackToAuto(): void
    {
        $configuration = new ApplicationCacheConfigurationFactory(new ApplicationCacheTestEnvironmentProvider([
            'APP_CACHE' => 'invalid',
        ]))->create('/tmp/project');

        self::assertSame(ApplicationCacheMode::AUTO, $configuration->mode);
        self::assertTrue($configuration->isEnabled());
    }

    public function testDevEnvironmentRefreshesOnlyWhenCacheIsEnabled(): void
    {
        $autoConfiguration = new ApplicationCacheConfigurationFactory(new ApplicationCacheTestEnvironmentProvider([
            'APP_ENV' => 'dev',
            'APP_CACHE' => 'auto',
        ]))->create('/tmp/project');
        $offConfiguration = new ApplicationCacheConfigurationFactory(new ApplicationCacheTestEnvironmentProvider([
            'APP_ENV' => 'dev',
            'APP_CACHE' => 'off',
        ]))->create('/tmp/project');

        self::assertTrue($autoConfiguration->shouldRefreshOnRequest());
        self::assertFalse($offConfiguration->isEnabled());
        self::assertFalse($offConfiguration->shouldRefreshOnRequest());
    }
}

final class ApplicationCacheTestEnvironmentProvider extends SystemEnvironmentProvider
{
    /**
     * @param array<string, string> $values
     */
    public function __construct(private readonly array $values)
    {
    }

    public function get(string $name, ?string $default = null): string
    {
        return $this->values[$name] ?? $default ?? '';
    }
}
