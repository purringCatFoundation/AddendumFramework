<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Routing;

use Ds\Vector;
use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\ApplicationCacheMode;
use PCF\Addendum\Application\Cache\PhpFileWriter;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Http\Routing\ActionScanner;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionCache;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class CompiledRouteCollectionCacheTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/addendum-route-cache-' . uniqid('', true);
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

    public function testOffModeBuildsWithoutWritingCompiledFiles(): void
    {
        $configuration = $this->configuration(ApplicationCacheMode::OFF);
        $routes = $this->cache($configuration)->loadOrBuild([$this->scanner()]);

        self::assertCount(1, $routes->getRoutesForMethod('GET'));
        self::assertFileDoesNotExist($configuration->routesFile());
        self::assertFileDoesNotExist($configuration->metadataFile());
    }

    public function testAutoModeLoadsExistingCompiledRoutes(): void
    {
        $configuration = $this->configuration(ApplicationCacheMode::AUTO);
        $this->cache($configuration)->warmup([$this->scanner()]);

        $routes = $this->cache($configuration)->loadOrBuild([]);

        self::assertCount(1, $routes->getRoutesForMethod('GET'));
        self::assertSame('/compiled-cache-fixture', $routes->getRoutesForMethod('GET')[0]->path);
    }

    public function testAutoModeRebuildsInvalidCompiledRoutes(): void
    {
        $configuration = $this->configuration(ApplicationCacheMode::AUTO);
        file_put_contents($configuration->routesFile(), "<?php\ndeclare(strict_types=1);\n\nreturn [];\n");

        $routes = $this->cache($configuration)->loadOrBuild([$this->scanner()]);

        self::assertSame('/compiled-cache-fixture', $routes->getRoutesForMethod('GET')[0]->path);
        self::assertFileExists($configuration->metadataFile());
    }

    public function testRequiredModeRethrowsInvalidCompiledRoutes(): void
    {
        $configuration = $this->configuration(ApplicationCacheMode::REQUIRED);
        file_put_contents($configuration->routesFile(), "<?php\ndeclare(strict_types=1);\n\nreturn [];\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must return a callable');

        $this->cache($configuration)->loadOrBuild([$this->scanner()]);
    }

    public function testWarmupWritesRoutesAndMetadata(): void
    {
        $configuration = $this->configuration(ApplicationCacheMode::AUTO);

        $this->cache($configuration)->warmup([$this->scanner()]);

        self::assertFileExists($configuration->routesFile());
        self::assertFileExists($configuration->metadataFile());
        self::assertSame(1, (require $configuration->metadataFile())['routeCount']);
    }

    private function configuration(ApplicationCacheMode $mode): ApplicationCacheConfiguration
    {
        return new ApplicationCacheConfiguration($mode, 'prod', $this->cacheDirectory);
    }

    private function cache(ApplicationCacheConfiguration $configuration): CompiledRouteCollectionCache
    {
        return new CompiledRouteCollectionCache(
            $configuration,
            new CompiledRouteCollectionGenerator(),
            new PhpFileWriter()
        );
    }

    private function scanner(): CompiledRouteCollectionCacheFixtureScanner
    {
        return new CompiledRouteCollectionCacheFixtureScanner([
            new ReflectionClass(CompiledRouteCollectionCacheFixtureAction::class),
        ]);
    }
}

final class CompiledRouteCollectionCacheFixtureScanner extends ActionScanner
{
    /**
     * @param list<ReflectionClass> $actions
     */
    public function __construct(private readonly array $actions)
    {
    }

    /**
     * @return Vector<ReflectionClass>
     */
    public function scanActions(): Vector
    {
        return new Vector($this->actions);
    }
}

#[Route('/compiled-cache-fixture', 'GET')]
final class CompiledRouteCollectionCacheFixtureAction
{
}
