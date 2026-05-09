<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Application\Cache;

use PCF\Addendum\Application\App;
use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\ApplicationCacheMode;
use PCF\Addendum\Application\Cache\CompiledHttpApplicationCache;
use PCF\Addendum\Application\Cache\CompiledHttpApplicationGenerator;
use PCF\Addendum\Application\Cache\PhpFileWriter;
use PCF\Addendum\Http\RouteCollection;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionGenerator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CompiledHttpApplicationCacheTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/addendum-compiled-app-' . uniqid('', true);
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

    public function testGeneratedAppLoadsCompiledRoutesWithoutReflection(): void
    {
        $code = new CompiledHttpApplicationGenerator()->generate();

        self::assertStringContainsString("__DIR__ . '/routes.php'", $code);
        self::assertStringNotContainsString('Reflection', $code);
        self::assertStringNotContainsString('ActionScanner', $code);
    }

    public function testLoadsCompiledApp(): void
    {
        $configuration = new ApplicationCacheConfiguration(
            ApplicationCacheMode::AUTO,
            'prod',
            $this->cacheDirectory
        );

        file_put_contents($configuration->routesFile(), new CompiledRouteCollectionGenerator()->generate(new RouteCollection()));
        $cache = new CompiledHttpApplicationCache(
            $configuration,
            new CompiledHttpApplicationGenerator(),
            new PhpFileWriter()
        );
        $cache->warmup();

        self::assertInstanceOf(App::class, $cache->load());
    }

    public function testMissingCompiledAppThrows(): void
    {
        $cache = new CompiledHttpApplicationCache(
            $this->configuration(),
            new CompiledHttpApplicationGenerator(),
            new PhpFileWriter()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $cache->load();
    }

    public function testCompiledAppFileMustReturnCallable(): void
    {
        $configuration = $this->configuration();
        file_put_contents($configuration->appFile(), "<?php\ndeclare(strict_types=1);\n\nreturn ['not callable'];\n");
        $cache = new CompiledHttpApplicationCache(
            $configuration,
            new CompiledHttpApplicationGenerator(),
            new PhpFileWriter()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return a callable');

        $cache->load();
    }

    public function testCompiledAppCallableMustReturnApp(): void
    {
        $configuration = $this->configuration();
        file_put_contents($configuration->appFile(), "<?php\ndeclare(strict_types=1);\n\nreturn static fn() => new \\stdClass();\n");
        $cache = new CompiledHttpApplicationCache(
            $configuration,
            new CompiledHttpApplicationGenerator(),
            new PhpFileWriter()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return App');

        $cache->load();
    }

    private function configuration(ApplicationCacheMode $mode = ApplicationCacheMode::AUTO): ApplicationCacheConfiguration
    {
        return new ApplicationCacheConfiguration(
            $mode,
            'prod',
            $this->cacheDirectory
        );
    }
}
