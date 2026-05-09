<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Cache;

use DateInterval;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Http\Middleware\HttpCache;
use PCF\Addendum\Http\Cache\CloudflareHttpCache;
use PCF\Addendum\Http\Cache\CloudflareHttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\HttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\HttpCacheConfigurationInterface;
use PCF\Addendum\Http\Cache\HttpCacheContext;
use PCF\Addendum\Http\Cache\HttpCacheHeader;
use PCF\Addendum\Http\Cache\HttpCacheKeyGenerator;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\HttpCachePolicy;
use PCF\Addendum\Http\Cache\HttpCacheRequestContext;
use PCF\Addendum\Http\Cache\HttpCacheRuntime;
use PCF\Addendum\Http\Cache\HttpResponseCache;
use PCF\Addendum\Http\Cache\RedisHttpCache;
use PCF\Addendum\Http\Cache\RedisHttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\Cache\VarnishHttpCache;
use PCF\Addendum\Http\Cache\VarnishHttpCacheBackendProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;

final class HttpCacheTest extends TestCase
{
    public function testAppliesPublicCacheHeaders(): void
    {
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'article', idAttribute: 'articleUuid')),
            new HttpCacheRuntime($this->varnish(), new VarnishHttpCacheBackendProvider())
        );

        $response = $middleware->process(
            new ServerRequest('GET', '/articles/1')->withAttribute('articleUuid', '1'),
            new HttpCacheFixtureHandler(new PsrResponse(200, ['Vary' => 'Accept']))
        );

        $this->assertSame('public, max-age=60, s-maxage=60', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('Accept', $response->getHeaderLine('Vary'));
        $this->assertSame('max-age=60', $response->getHeaderLine('Surrogate-Control'));
        $this->assertSame('article:1', $response->getHeaderLine('Surrogate-Key'));
    }

    public function testAppliesGuestAwareCacheHeaders(): void
    {
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::GUEST_AWARE, maxAge: 120, resource: 'feed')),
            new HttpCacheRuntime($this->redis(), new HttpCacheArrayBackendProvider())
        );

        $response = $middleware->process(
            new ServerRequest('GET', '/feed'),
            new HttpCacheFixtureHandler(new PsrResponse(200))
        );

        $this->assertSame('public, max-age=120, s-maxage=120', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('X-Auth-State', $response->getHeaderLine('Vary'));
        $this->assertSame('guest', $response->getHeaderLine('X-Auth-State'));
    }

    public function testUserAwareCacheRequiresTrustedContext(): void
    {
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::USER_AWARE, maxAge: 120, resource: 'profile')),
            new HttpCacheRuntime($this->cloudflare(), new CloudflareHttpCacheBackendProvider())
        );

        $request = new ServerRequest('GET', '/profile')->withAttribute('user_uuid', 'user-1');
        $response = $middleware->process($request, new HttpCacheFixtureHandler(new PsrResponse(200)));

        $this->assertSame('private, no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('', $response->getHeaderLine('Cache-Tag'));
        $this->assertSame('', $response->getHeaderLine('Vary'));
    }

    public function testUserAwareCacheAcceptsTrustedContext(): void
    {
        $_ENV['TEST_HTTP_CACHE_SECRET'] = 'cache-secret';
        $context = new HttpCacheContext(secretEnv: 'TEST_HTTP_CACHE_SECRET');
        $signature = $context->signUserContext('ctx-user-1', 'user-1', 'user');
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::USER_AWARE, maxAge: 120, resource: 'profile')),
            new HttpCacheRuntime(new CloudflareHttpCache(context: $context), new CloudflareHttpCacheBackendProvider())
        );

        $request = new ServerRequest('GET', '/profile')
            ->withAttribute('user_uuid', 'user-1')
            ->withAttribute('token_type', TokenType::USER)
            ->withHeader('X-User-Context-Hash', 'ctx-user-1')
            ->withHeader('X-User-Context-Signature', (string) $signature);

        try {
            $response = $middleware->process($request, new HttpCacheFixtureHandler(new PsrResponse(200)));
        } finally {
            unset($_ENV['TEST_HTTP_CACHE_SECRET']);
        }

        $this->assertSame('public, max-age=120, s-maxage=120', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('X-User-Context-Hash', $response->getHeaderLine('Vary'));
        $this->assertSame('profile', $response->getHeaderLine('Cache-Tag'));
    }

    public function testDoesNotCacheErrorsByDefault(): void
    {
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 120, resource: 'missing')),
            new HttpCacheRuntime($this->redis(), new HttpCacheArrayBackendProvider())
        );

        $response = $middleware->process(new ServerRequest('GET', '/missing'), new HttpCacheFixtureHandler(new PsrResponse(404)));

        $this->assertSame('private, no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testReadsAndWritesCacheForGetRequests(): void
    {
        $backend = new HttpCacheArrayBackendProvider();
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 120, resource: 'article', idAttribute: 'articleUuid')),
            new HttpCacheRuntime($this->redis(), $backend)
        );
        $handler = new HttpCacheCountingHandler(new PsrResponse(200, [], 'fresh'));
        $request = new ServerRequest('GET', '/articles/1')->withAttribute('articleUuid', '1');

        $first = $middleware->process($request, $handler);
        $second = $middleware->process($request, $handler);

        $this->assertSame('MISS', $first->getHeaderLine('X-Redis-Cache'));
        $this->assertSame('HIT', $second->getHeaderLine('X-Redis-Cache'));
        $this->assertSame('fresh', (string) $second->getBody());
        $this->assertSame(1, $handler->calls);
    }

    public function testCachesOptionsRequests(): void
    {
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'articles')),
            new HttpCacheRuntime($this->redis(), new HttpCacheArrayBackendProvider())
        );
        $handler = new HttpCacheCountingHandler(new PsrResponse(204, ['Allow' => 'GET, OPTIONS']));
        $request = new ServerRequest('OPTIONS', '/articles');

        $middleware->process($request, $handler);
        $response = $middleware->process($request, $handler);

        $this->assertSame('HIT', $response->getHeaderLine('X-Redis-Cache'));
        $this->assertSame(1, $handler->calls);
    }

    public function testRedisProviderAddsDebugHeadersWhenEnabled(): void
    {
        $configuration = new RedisHttpCache(context: new HttpCacheContext(debugHeaders: true));
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'article', idAttribute: 'articleUuid')),
            new HttpCacheRuntime(
                $configuration,
                new RedisHttpCacheBackendProvider(
                    new HttpResponseCache(new HttpCacheArrayCache(), $configuration->keyPrefix),
                    new HttpCacheKeyGenerator()
                )
            )
        );
        $handler = new HttpCacheCountingHandler(new PsrResponse(200, [], 'fresh'));
        $request = new ServerRequest('GET', '/articles/1')->withAttribute('articleUuid', '1');

        $first = $middleware->process($request, $handler);
        $second = $middleware->process($request, $handler);

        $this->assertSame('MISS', $first->getHeaderLine('X-Http-Cache'));
        $this->assertSame('redis', $first->getHeaderLine('X-Http-Cache-Provider'));
        $this->assertSame('MISS', $first->getHeaderLine('X-Redis-Cache'));
        $this->assertSame('HIT', $second->getHeaderLine('X-Http-Cache'));
        $this->assertSame('redis', $second->getHeaderLine('X-Http-Cache-Provider'));
        $this->assertSame('HIT', $second->getHeaderLine('X-Redis-Cache'));
    }

    public function testRedisProviderOmitsDebugHeadersByDefault(): void
    {
        $configuration = $this->redis();
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'article', idAttribute: 'articleUuid')),
            new HttpCacheRuntime(
                $configuration,
                new RedisHttpCacheBackendProvider(
                    new HttpResponseCache(new HttpCacheArrayCache(), $configuration->keyPrefix),
                    new HttpCacheKeyGenerator()
                )
            )
        );
        $request = new ServerRequest('GET', '/articles/1')->withAttribute('articleUuid', '1');
        $response = $middleware->process($request, new HttpCacheCountingHandler(new PsrResponse(200, [], 'fresh')));

        $this->assertSame('', $response->getHeaderLine('X-Http-Cache'));
        $this->assertSame('', $response->getHeaderLine('X-Http-Cache-Provider'));
        $this->assertSame('', $response->getHeaderLine('X-Redis-Cache'));
    }

    public function testInvalidatesResourcesForMutationRequests(): void
    {
        $backend = new HttpCacheArrayBackendProvider();
        $policies = $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 120, resource: 'article', idAttribute: 'articleUuid'));
        $cacheMiddleware = new HttpCache($policies, new HttpCacheRuntime($this->redis(), $backend));
        $invalidateMiddleware = new HttpCache($policies, new HttpCacheRuntime($this->redis(), $backend));
        $getHandler = new HttpCacheCountingHandler(new PsrResponse(200, [], 'fresh'));
        $request = new ServerRequest('GET', '/articles/1')->withAttribute('articleUuid', '1');

        $cacheMiddleware->process($request, $getHandler);
        $cacheMiddleware->process($request, $getHandler);

        $mutationResponse = $invalidateMiddleware->process(
            new ServerRequest('PATCH', '/articles/1')->withAttribute('articleUuid', '1'),
            new HttpCacheFixtureHandler(new PsrResponse(204))
        );
        $cacheMiddleware->process($request, $getHandler);

        $this->assertSame('article:1', $mutationResponse->getHeaderLine('X-Cache-Invalidate'));
        $this->assertSame('private, no-store', $mutationResponse->getHeaderLine('Cache-Control'));
        $this->assertSame(2, $getHandler->calls);
    }

    public function testDoesNotInvalidateResourcesForFailedMutationRequests(): void
    {
        $backend = new HttpCacheArrayBackendProvider();
        $policies = $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 120, resource: 'article', idAttribute: 'articleUuid'));
        $cacheMiddleware = new HttpCache($policies, new HttpCacheRuntime($this->redis(), $backend));
        $invalidateMiddleware = new HttpCache($policies, new HttpCacheRuntime($this->redis(), $backend));
        $getHandler = new HttpCacheCountingHandler(new PsrResponse(200, [], 'fresh'));
        $request = new ServerRequest('GET', '/articles/1')->withAttribute('articleUuid', '1');

        $cacheMiddleware->process($request, $getHandler);
        $invalidateMiddleware->process(
            new ServerRequest('DELETE', '/articles/1')->withAttribute('articleUuid', '1'),
            new HttpCacheFixtureHandler(new PsrResponse(500))
        );
        $response = $cacheMiddleware->process($request, $getHandler);

        $this->assertSame('HIT', $response->getHeaderLine('X-Redis-Cache'));
        $this->assertSame(1, $getHandler->calls);
    }

    public function testPrivatePolicySkipsBackendAndRemovesProxyHeaders(): void
    {
        $backend = new HttpCacheCountingBackendProvider();
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PRIVATE, maxAge: 60, resource: 'account')),
            new HttpCacheRuntime($this->redis(), $backend)
        );

        $response = $middleware->process(
            new ServerRequest('GET', '/account'),
            new HttpCacheFixtureHandler(new PsrResponse(200, [
                'Surrogate-Control' => 'max-age=60',
                'Surrogate-Key' => 'account',
                'X-Accel-Expires' => '60',
                'X-Cache-Tags' => 'account',
                'Souin-Cache-Tags' => 'account',
                'Cache-Tag' => 'account',
                'CDN-Cache-Control' => 'public, max-age=60',
                'Cloudflare-CDN-Cache-Control' => 'public, max-age=60',
            ]))
        );

        $this->assertSame(0, $backend->readCalls);
        $this->assertSame(0, $backend->writeCalls);
        $this->assertSame('private, no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('', $response->getHeaderLine('Surrogate-Control'));
        $this->assertSame('', $response->getHeaderLine('Surrogate-Key'));
        $this->assertSame('', $response->getHeaderLine('X-Accel-Expires'));
        $this->assertSame('', $response->getHeaderLine('X-Cache-Tags'));
        $this->assertSame('', $response->getHeaderLine('Souin-Cache-Tags'));
        $this->assertSame('', $response->getHeaderLine('Cache-Tag'));
        $this->assertSame('', $response->getHeaderLine('CDN-Cache-Control'));
        $this->assertSame('', $response->getHeaderLine('Cloudflare-CDN-Cache-Control'));
    }

    public function testCacheErrorsTrueAllowsErrorResponsesToBeCached(): void
    {
        $backend = new HttpCacheCountingBackendProvider();
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 120, resource: 'missing', cacheErrors: true)),
            new HttpCacheRuntime($this->redis(), $backend)
        );

        $response = $middleware->process(new ServerRequest('GET', '/missing'), new HttpCacheFixtureHandler(new PsrResponse(404)));

        $this->assertSame(1, $backend->readCalls);
        $this->assertSame(1, $backend->writeCalls);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('public, max-age=120, s-maxage=120', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('MISS', $response->getHeaderLine('X-Cache-Write'));
    }

    public function testMutationWithoutResourceNamesDoesNotInvalidate(): void
    {
        $backend = new HttpCacheCountingBackendProvider();
        $middleware = new HttpCache(
            $this->policies(new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 120, resource: 'article', idAttribute: 'articleUuid')),
            new HttpCacheRuntime($this->redis(), $backend)
        );

        $response = $middleware->process(new ServerRequest('PATCH', '/articles/1'), new HttpCacheFixtureHandler(new PsrResponse(204)));

        $this->assertSame(0, $backend->invalidateCalls);
        $this->assertSame('private, no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('', $response->getHeaderLine('X-Cache-Invalidate'));
    }

    private function policies(ResourcePolicy ...$policies): ResourcePolicyCollection
    {
        return new ResourcePolicyCollection($policies);
    }

    private function redis(?HttpCacheContext $context = null): RedisHttpCache
    {
        return new RedisHttpCache($context ?? new HttpCacheContext());
    }

    private function varnish(): VarnishHttpCache
    {
        return new VarnishHttpCache(new HttpCacheContext());
    }

    private function cloudflare(?HttpCacheContext $context = null): CloudflareHttpCache
    {
        return new CloudflareHttpCache($context ?? new HttpCacheContext());
    }
}

final readonly class HttpCacheFixtureHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response;
    }
}

final class HttpCacheCountingHandler implements RequestHandlerInterface
{
    public int $calls = 0;

    public function __construct(
        private ResponseInterface $response
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->calls++;

        return $this->response;
    }
}

final class HttpCacheArrayBackendProvider implements HttpCacheBackendProvider
{
    private HttpResponseCache $cache;

    public function __construct()
    {
        $this->cache = new HttpResponseCache(new HttpCacheArrayCache());
    }

    public function supports(HttpCacheConfigurationInterface $configuration): bool
    {
        return $configuration instanceof RedisHttpCache;
    }

    public function context(HttpCacheConfigurationInterface $configuration): HttpCacheContext
    {
        return $configuration instanceof RedisHttpCache ? $configuration->context : new HttpCacheContext();
    }

    public function read(HttpCacheConfigurationInterface $configuration, ResourcePolicyCollection $policies, ServerRequestInterface $request, HttpCacheRequestContext $context): ?ResponseInterface
    {
        $policy = $policies->toHttpCachePolicy($request);
        $response = $this->cache->get(md5($request->getMethod() . '|' . $request->getUri()->getPath() . '|' . $policy->mode->value));

        return $response?->withHeader('X-Redis-Cache', 'HIT');
    }

    public function write(HttpCacheConfigurationInterface $configuration, ResourcePolicyCollection $policies, ServerRequestInterface $request, HttpCacheRequestContext $context, ResponseInterface $response): ResponseInterface
    {
        $policy = $policies->toHttpCachePolicy($request);
        $this->cache->set(
            md5($request->getMethod() . '|' . $request->getUri()->getPath() . '|' . $policy->mode->value),
            $response,
            $policy->redisTtl() ?? 60,
            $policies->resourceNames($request)
        );

        return $response->withHeader('X-Redis-Cache', 'MISS');
    }

    public function invalidate(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $resources = $policies->resourceNames($request);
        $this->cache->invalidate($resources);

        return $response->withHeader('X-Cache-Invalidate', HttpCacheHeader::cacheTags($resources));
    }

    public function buildHeaders(
        HttpCacheConfigurationInterface $configuration,
        HttpCachePolicy $policy,
        HttpCacheRequestContext $context,
        ResponseInterface $response
    ): ResponseInterface
    {
        return $response;
    }
}

final class HttpCacheArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}

final class HttpCacheCountingBackendProvider implements HttpCacheBackendProvider
{
    public int $readCalls = 0;
    public int $writeCalls = 0;
    public int $invalidateCalls = 0;

    public function supports(HttpCacheConfigurationInterface $configuration): bool
    {
        return $configuration instanceof RedisHttpCache;
    }

    public function context(HttpCacheConfigurationInterface $configuration): HttpCacheContext
    {
        return $configuration instanceof RedisHttpCache ? $configuration->context : new HttpCacheContext();
    }

    public function read(HttpCacheConfigurationInterface $configuration, ResourcePolicyCollection $policies, ServerRequestInterface $request, HttpCacheRequestContext $context): ?ResponseInterface
    {
        $this->readCalls++;

        return null;
    }

    public function write(HttpCacheConfigurationInterface $configuration, ResourcePolicyCollection $policies, ServerRequestInterface $request, HttpCacheRequestContext $context, ResponseInterface $response): ResponseInterface
    {
        $this->writeCalls++;

        return $response->withHeader('X-Cache-Write', 'MISS');
    }

    public function invalidate(
        HttpCacheConfigurationInterface $configuration,
        ResourcePolicyCollection $policies,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $this->invalidateCalls++;

        return $response->withHeader('X-Cache-Invalidate', 'called');
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
