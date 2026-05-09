<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\ApplicationCacheMode;
use PCF\Addendum\Application\Cache\CompiledCacheInspector;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Command\CacheDebugCommand;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteCollection;
use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CacheDebugCommandTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/addendum-cache-debug-' . uniqid('', true);
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

    public function testListsCompiledRoutes(): void
    {
        $this->writeCache($this->routes());
        $tester = new CommandTester($this->command());

        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('GET /v1/users/:userUuid', $tester->getDisplay());
        self::assertStringContainsString('POST /v1/users', $tester->getDisplay());
    }

    public function testListsCompiledRouteDetails(): void
    {
        $this->writeCache($this->routes());
        $tester = new CommandTester($this->command());

        $tester->execute(['--details' => true]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('GET /v1/users/:userUuid => ' . CacheDebugFixtureAction::class, $tester->getDisplay());
        self::assertStringContainsString('"middleware"', $tester->getDisplay());
        self::assertStringContainsString('"policies"', $tester->getDisplay());
        self::assertStringContainsString('"resource": "user"', $tester->getDisplay());
        self::assertStringContainsString('"pattern"', $tester->getDisplay());
        self::assertStringContainsString('#^/v1/users/(?P<userUuid>[^/]+)$#', $tester->getDisplay());
        self::assertStringNotContainsString('"actionClass"', $tester->getDisplay());
    }

    public function testFiltersCompiledRoutesByRequestPath(): void
    {
        $this->writeCache($this->routes());
        $tester = new CommandTester($this->command());

        $tester->execute(['--path' => '/v1/users/123']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('GET /v1/users/:userUuid', $tester->getDisplay());
        self::assertStringNotContainsString('POST /v1/users', $tester->getDisplay());
    }

    public function testJsonOutputIncludesRoutesList(): void
    {
        $this->writeCache($this->routes());
        $tester = new CommandTester($this->command());

        $tester->execute(['--json' => true]);

        $payload = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertTrue($payload['routes']['valid']);
        self::assertFalse($payload['app']['valid']);
        self::assertCount(2, $payload['routesList']);
        self::assertSame('/v1/users', $payload['routesList'][0]['path']);
        self::assertSame('/v1/users/:userUuid', $payload['routesList'][1]['path']);
    }

    public function testStrictModeFailsWhenCompiledCacheIsIncomplete(): void
    {
        $tester = new CommandTester($this->command());

        $tester->execute(['--strict' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('routes.php: missing', $tester->getDisplay());
        self::assertStringContainsString('app.php: missing', $tester->getDisplay());
    }

    public function testReportsWhenPathFilterDoesNotMatchRoutes(): void
    {
        $this->writeCache($this->routes());
        $tester = new CommandTester($this->command());

        $tester->execute(['--path' => '/v1/missing']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No routes matching /v1/missing', $tester->getDisplay());
    }

    private function command(): CacheDebugCommand
    {
        return new CacheDebugCommand(
            new ApplicationCacheConfiguration(
                ApplicationCacheMode::AUTO,
                'prod',
                $this->cacheDirectory
            ),
            new CompiledCacheInspector()
        );
    }

    private function routes(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->addRoute('GET', new RegisteredRoute(
            '#^/v1/users/(?P<userUuid>[^/]+)$#',
            CacheDebugFixtureAction::class,
            [new RouteMiddleware(Auth::class, new MiddlewareOptions())],
            new ResourcePolicyCollection([
                new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'user', idAttribute: 'userUuid'),
            ]),
            '/v1/users/:userUuid'
        ));
        $routes->addRoute('POST', new RegisteredRoute(
            '#^/v1/users$#',
            CacheDebugFixtureAction::class,
            [],
            new ResourcePolicyCollection([
                new ResourcePolicy(mode: HttpCacheMode::PRIVATE),
            ]),
            '/v1/users'
        ));

        return $routes;
    }

    private function writeCache(RouteCollection $routes): void
    {
        file_put_contents($this->cacheDirectory . '/routes.php', new CompiledRouteCollectionGenerator()->generate($routes));
        file_put_contents($this->cacheDirectory . '/metadata.php', "<?php\ndeclare(strict_types=1);\n\nreturn ['routeCount' => 2];\n");
    }
}

final class CacheDebugFixtureAction
{
}
