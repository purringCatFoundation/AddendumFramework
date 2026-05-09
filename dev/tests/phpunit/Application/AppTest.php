<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Application;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Application\App;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Http\Cache\HttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\HttpCacheConfigurationInterface;
use PCF\Addendum\Http\Cache\HttpCacheContext;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\HttpCachePolicy;
use PCF\Addendum\Http\Cache\HttpCacheRequestContext;
use PCF\Addendum\Http\Cache\HttpCacheRuntime;
use PCF\Addendum\Http\Cache\RedisHttpCache;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

final class AppTest extends TestCase
{
    public function testReturnsMethodNotAllowedWithAllowHeader(): void
    {
        $router = new AppTestRouter(null, ['GET', 'PATCH']);
        $app = new App($router, new NullLogger(), $this->runtime());

        $response = $app->handle(new ServerRequest('POST', '/v1/users/me'));

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, PATCH', $response->getHeaderLine('Allow'));
    }

    public function testReturnsNotFoundWhenPathDoesNotExist(): void
    {
        $router = new AppTestRouter(null, []);
        $app = new App($router, new NullLogger(), $this->runtime());

        $response = $app->handle(new ServerRequest('GET', '/missing'));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRejectsUnsupportedAcceptHeader(): void
    {
        $route = new RegisteredRoute('#^/test$#', AppTestAction::class, [], $this->cachedPolicies());
        $router = new AppTestRouter($route, []);
        $app = new App($router, new NullLogger(), $this->runtime());

        $request = new ServerRequest('GET', '/test')->withHeader('Accept', 'text/plain');
        $response = $app->handle($request);

        $this->assertSame(406, $response->getStatusCode());
    }

    public function testRejectsUnsupportedContentTypeForJsonBody(): void
    {
        $route = new RegisteredRoute('#^/test$#', AppTestAction::class, [], $this->cachedPolicies());
        $router = new AppTestRouter($route, []);
        $app = new App($router, new NullLogger(), $this->runtime());

        $request = new ServerRequest('POST', '/test', [], '{"ok":true}')
            ->withHeader('Content-Type', 'text/plain');
        $response = $app->handle($request);

        $this->assertSame(415, $response->getStatusCode());
    }

    public function testAppliesHttpCacheAroundMatchedAction(): void
    {
        $backend = new AppTestHttpCacheBackendProvider();
        $route = new RegisteredRoute('#^/cached$#', AppCachedAction::class, [], $this->cachedPolicies());
        $router = new AppTestRouter($route, []);
        $app = new App($router, new NullLogger(), new HttpCacheRuntime($this->redis(), $backend));

        $response = $app->handle(new ServerRequest('GET', '/cached'));

        $this->assertSame(1, $backend->reads);
        $this->assertSame(1, $backend->writes);
        $this->assertSame('WRITE', $response->getHeaderLine('X-App-Http-Cache'));
        $this->assertSame('public, max-age=60, s-maxage=60', $response->getHeaderLine('Cache-Control'));
    }

    public function testHttpCacheHitDoesNotCreateActionHandler(): void
    {
        AppCachedActionFactory::$created = 0;

        $backend = new AppTestHttpCacheBackendProvider(new Response(200, [], '{"cached":true}'));
        $route = new RegisteredRoute('#^/cached$#', AppCachedAction::class, [], $this->cachedPolicies());
        $router = new AppTestRouter($route, []);
        $app = new App($router, new NullLogger(), new HttpCacheRuntime($this->redis(), $backend));

        $response = $app->handle(new ServerRequest('GET', '/cached'));

        $this->assertSame(1, $backend->reads);
        $this->assertSame(0, $backend->writes);
        $this->assertSame(0, AppCachedActionFactory::$created);
        $this->assertSame('{"cached":true}', (string) $response->getBody());
    }

    private function cachedPolicies(): ResourcePolicyCollection
    {
        return new ResourcePolicyCollection([
            new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'cached'),
        ]);
    }

    private function runtime(): HttpCacheRuntime
    {
        return new HttpCacheRuntime($this->redis(), new AppTestHttpCacheBackendProvider());
    }

    private function redis(): RedisHttpCache
    {
        return new RedisHttpCache(new HttpCacheContext());
    }
}

final class AppTestRouter extends Router
{
    public function __construct(
        private readonly ?RegisteredRoute $route,
        private readonly array $allowedMethods
    ) {
    }

    public function match(\Psr\Http\Message\ServerRequestInterface $request): ?\PCF\Addendum\Http\RouteMatch
    {
        if ($this->route === null) {
            return null;
        }

        return $this->route->createMatchResult($request);
    }

    public function getAllowedMethodsForPath(string $path): \Ds\Vector
    {
        return new \Ds\Vector($this->allowedMethods);
    }
}

final class AppTestAction
{
    public function __invoke(): array
    {
        return ['ok' => true];
    }
}

#[ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'cached')]
final class AppCachedAction
{
    public function __invoke(mixed $request = null): array
    {
        return ['cached' => false];
    }
}

final class AppCachedActionFactory
{
    public static int $created = 0;

    public function create(): AppCachedAction
    {
        self::$created++;

        return new AppCachedAction();
    }
}

final class AppTestHttpCacheBackendProvider implements HttpCacheBackendProvider
{
    public int $reads = 0;
    public int $writes = 0;

    public function __construct(
        private readonly ?ResponseInterface $cachedResponse = null
    ) {
    }

    public function supports(HttpCacheConfigurationInterface $configuration): bool
    {
        return true;
    }

    public function context(HttpCacheConfigurationInterface $configuration): HttpCacheContext
    {
        return new HttpCacheContext();
    }

    public function read(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        HttpCacheRequestContext $context
    ): ?ResponseInterface {
        $this->reads++;

        return $this->cachedResponse;
    }

    public function write(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface {
        $this->writes++;

        return $response->withHeader('X-App-Http-Cache', 'WRITE');
    }

    public function invalidate(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        return $response;
    }

    public function buildHeaders(
        HttpCacheConfigurationInterface $configuration,
        HttpCachePolicy $policy,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface {
        return $response;
    }
}
