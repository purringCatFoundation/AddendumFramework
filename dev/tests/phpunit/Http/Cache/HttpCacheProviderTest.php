<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Cache;

use PCF\Addendum\Http\Cache\CaddyHttpCache;
use PCF\Addendum\Http\Cache\CaddyHttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\CloudflareHttpCache;
use PCF\Addendum\Http\Cache\CloudflareHttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\HttpCacheBackendProviderFactory;
use PCF\Addendum\Http\Cache\HttpCacheContext;
use PCF\Addendum\Http\Cache\HttpCacheHeader;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\HttpCachePolicy;
use PCF\Addendum\Http\Cache\HttpCacheRequestContext;
use PCF\Addendum\Http\Cache\RedisHttpCache;
use PCF\Addendum\Http\Cache\RedisHttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\NginxHttpCache;
use PCF\Addendum\Http\Cache\NginxHttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\NoneHttpCache;
use PCF\Addendum\Http\Cache\NoneHttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\Cache\VarnishHttpCache;
use PCF\Addendum\Http\Cache\VarnishHttpCacheBackendProvider;
use PCF\Addendum\Attribute\ResourcePolicy;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class HttpCacheProviderTest extends TestCase
{
    public function testBuildsCacheControlHeader(): void
    {
        $policy = $this->policy();

        $this->assertSame(
            'public, max-age=60, s-maxage=120, stale-while-revalidate=30, stale-if-error=90',
            HttpCacheHeader::cacheControl($policy)
        );
    }

    public function testVarnishHeaders(): void
    {
        $response = new VarnishHttpCacheBackendProvider()->buildHeaders($this->varnish(), $this->policy(), $this->context(), new PsrResponse());

        $this->assertSame('max-age=120', $response->getHeaderLine('Surrogate-Control'));
        $this->assertSame('article:1 user-1', $response->getHeaderLine('Surrogate-Key'));
    }

    public function testNginxHeaders(): void
    {
        $response = new NginxHttpCacheBackendProvider()->buildHeaders($this->nginx(), $this->policy(), $this->context(), new PsrResponse());

        $this->assertSame('120', $response->getHeaderLine('X-Accel-Expires'));
        $this->assertSame('article:1,user-1', $response->getHeaderLine('X-Cache-Tags'));
    }

    public function testCaddySouinHeaders(): void
    {
        $response = new CaddyHttpCacheBackendProvider()->buildHeaders(
            new CaddyHttpCache(context: new HttpCacheContext(), cacheHandler: CaddyHttpCache::SOUIN),
            $this->policy(),
            $this->context(),
            new PsrResponse()
        );

        $this->assertSame('article:1,user-1', $response->getHeaderLine('Souin-Cache-Tags'));
    }

    public function testCloudflareHeaders(): void
    {
        $response = new CloudflareHttpCacheBackendProvider()->buildHeaders($this->cloudflare(), $this->policy(), $this->context(), new PsrResponse());

        $this->assertSame(
            'public, max-age=60, s-maxage=120, stale-while-revalidate=30, stale-if-error=90',
            $response->getHeaderLine('CDN-Cache-Control')
        );
        $this->assertSame('article:1,user-1', $response->getHeaderLine('Cache-Tag'));
    }

    public function testProviderDebugHeadersInDevContext(): void
    {
        $response = new VarnishHttpCacheBackendProvider()->buildHeaders(
            new VarnishHttpCache(context: new HttpCacheContext(debugHeaders: true)),
            $this->policy(),
            $this->context(),
            new PsrResponse()
        );

        $this->assertSame('MISS', $response->getHeaderLine('X-Http-Cache'));
        $this->assertSame('varnish', $response->getHeaderLine('X-Http-Cache-Provider'));
    }

    public function testRedisProviderIsCreatedByFactory(): void
    {
        $provider = new HttpCacheBackendProviderFactory()->create($this->redis());

        $this->assertInstanceOf(RedisHttpCacheBackendProvider::class, $provider);
    }

    public function testNoneProviderIsCreatedByFactory(): void
    {
        $provider = new HttpCacheBackendProviderFactory()->create(new NoneHttpCache(new HttpCacheContext()));

        $this->assertInstanceOf(NoneHttpCacheBackendProvider::class, $provider);
    }

    public function testProxyProvidersAreCreatedByFactory(): void
    {
        $factory = new HttpCacheBackendProviderFactory();

        $this->assertInstanceOf(VarnishHttpCacheBackendProvider::class, $factory->create($this->varnish()));
        $this->assertInstanceOf(NginxHttpCacheBackendProvider::class, $factory->create($this->nginx()));
        $this->assertInstanceOf(CaddyHttpCacheBackendProvider::class, $factory->create(new CaddyHttpCache(new HttpCacheContext())));
        $this->assertInstanceOf(CloudflareHttpCacheBackendProvider::class, $factory->create($this->cloudflare()));
    }

    public function testVarnishInvalidationHeaders(): void
    {
        $response = new VarnishHttpCacheBackendProvider()->invalidate(
            $this->varnish(),
            $this->resourcePolicies(),
            $this->resourceRequest(),
            new PsrResponse(204)
        );

        $this->assertSame('article:1,articles', $response->getHeaderLine('X-Cache-Invalidate'));
        $this->assertSame('article:1 articles', $response->getHeaderLine('Surrogate-Key'));
    }

    public function testNginxInvalidationHeaders(): void
    {
        $response = new NginxHttpCacheBackendProvider()->invalidate(
            $this->nginx(),
            $this->resourcePolicies(),
            $this->resourceRequest(),
            new PsrResponse(204)
        );

        $this->assertSame('article:1,articles', $response->getHeaderLine('X-Cache-Invalidate'));
        $this->assertSame('article:1,articles', $response->getHeaderLine('X-Cache-Tags'));
    }

    public function testCaddyInvalidationHeaders(): void
    {
        $response = new CaddyHttpCacheBackendProvider()->invalidate(
            new CaddyHttpCache(context: new HttpCacheContext(), cacheHandler: CaddyHttpCache::SOUIN),
            $this->resourcePolicies(),
            $this->resourceRequest(),
            new PsrResponse(204)
        );

        $this->assertSame('article:1,articles', $response->getHeaderLine('X-Cache-Invalidate'));
        $this->assertSame('article:1,articles', $response->getHeaderLine('Souin-Cache-Tags'));
    }

    public function testCloudflareInvalidationHeaders(): void
    {
        $response = new CloudflareHttpCacheBackendProvider()->invalidate(
            $this->cloudflare(),
            $this->resourcePolicies(),
            $this->resourceRequest(),
            new PsrResponse(204)
        );

        $this->assertSame('article:1,articles', $response->getHeaderLine('X-Cache-Invalidate'));
        $this->assertSame('article:1,articles', $response->getHeaderLine('Cache-Tag'));
    }

    private function policy(): HttpCachePolicy
    {
        return new HttpCachePolicy(
            mode: HttpCacheMode::PUBLIC,
            maxAge: 60,
            sharedMaxAge: 120,
            staleWhileRevalidate: 30,
            staleIfError: 90,
            vary: [],
            tags: ['article:1', 'user 1'],
            cacheErrors: false
        );
    }

    private function context(): HttpCacheRequestContext
    {
        return new HttpCacheRequestContext(
            authenticated: false,
            userUuid: null,
            tokenType: null,
            authState: 'guest',
            userContextHash: null,
            trustedUserContext: false
        );
    }

    private function resourcePolicies(): ResourcePolicyCollection
    {
        return new ResourcePolicyCollection([
            new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'article', idAttribute: 'articleUuid'),
            new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'articles'),
        ]);
    }

    private function resourceRequest(): ServerRequest
    {
        return new ServerRequest('PATCH', '/articles/1')->withAttribute('articleUuid', '1');
    }

    private function redis(): RedisHttpCache
    {
        return new RedisHttpCache(new HttpCacheContext());
    }

    private function varnish(): VarnishHttpCache
    {
        return new VarnishHttpCache(new HttpCacheContext());
    }

    private function nginx(): NginxHttpCache
    {
        return new NginxHttpCache(new HttpCacheContext());
    }

    private function cloudflare(): CloudflareHttpCache
    {
        return new CloudflareHttpCache(new HttpCacheContext());
    }
}
