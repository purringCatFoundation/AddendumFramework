<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Cache;

use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Http\Cache\CloudflareHttpCache;
use PCF\Addendum\Http\Cache\HttpCacheConfigurationFactory;
use PCF\Addendum\Http\Cache\RedisHttpCache;
use PCF\Addendum\Http\Cache\VarnishHttpCache;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HttpCacheConfigurationFactoryTest extends TestCase
{
    public function testMissingProviderDisablesHttpCache(): void
    {
        $factory = new HttpCacheConfigurationFactory(new HttpCacheTestEnvironmentProvider([]));

        $this->assertNull($factory->create());
    }

    public function testNoneProviderDisablesHttpCache(): void
    {
        $factory = new HttpCacheConfigurationFactory(new HttpCacheTestEnvironmentProvider(['HTTP_CACHE_PROVIDER' => 'none']));

        $this->assertNull($factory->create());
    }

    public function testCreatesRedisConfiguration(): void
    {
        $factory = new HttpCacheConfigurationFactory(new HttpCacheTestEnvironmentProvider([
            'HTTP_CACHE_PROVIDER' => 'redis',
            'REDIS_HTTP_CACHE_HOST' => 'redis',
            'REDIS_HTTP_CACHE_DATABASE' => '2',
            'REDIS_HTTP_CACHE_KEY_PREFIX' => 'test:http_cache:',
        ]));

        $configuration = $factory->create();

        $this->assertInstanceOf(RedisHttpCache::class, $configuration);
        $this->assertSame('redis', $configuration->host);
        $this->assertSame(2, $configuration->database);
        $this->assertSame('test:http_cache:', $configuration->keyPrefix);
    }

    public function testDevEnvironmentEnablesDebugHeaders(): void
    {
        $factory = new HttpCacheConfigurationFactory(new HttpCacheTestEnvironmentProvider([
            'APP_ENV' => 'dev',
            'HTTP_CACHE_PROVIDER' => 'redis',
        ]));

        $configuration = $factory->create();

        $this->assertInstanceOf(RedisHttpCache::class, $configuration);
        $this->assertTrue($configuration->context->debugHeaders);
        $this->assertSame('X-Http-Cache', $configuration->context->debugHeader);
        $this->assertSame('X-Http-Cache-Provider', $configuration->context->debugProviderHeader);
    }

    public function testDebugHeadersCanBeDisabledInDevEnvironment(): void
    {
        $factory = new HttpCacheConfigurationFactory(new HttpCacheTestEnvironmentProvider([
            'APP_ENV' => 'dev',
            'HTTP_CACHE_PROVIDER' => 'redis',
            'HTTP_CACHE_DEBUG_HEADERS' => 'false',
        ]));

        $configuration = $factory->create();

        $this->assertInstanceOf(RedisHttpCache::class, $configuration);
        $this->assertFalse($configuration->context->debugHeaders);
    }

    public function testCreatesVarnishConfiguration(): void
    {
        $factory = new HttpCacheConfigurationFactory(new HttpCacheTestEnvironmentProvider([
            'HTTP_CACHE_PROVIDER' => 'varnish',
            'VARNISH_HTTP_CACHE_TAG_HEADER' => 'X-Tags',
        ]));

        $configuration = $factory->create();

        $this->assertInstanceOf(VarnishHttpCache::class, $configuration);
        $this->assertSame('X-Tags', $configuration->tagHeader);
    }

    public function testCreatesCloudflareConfiguration(): void
    {
        $factory = new HttpCacheConfigurationFactory(new HttpCacheTestEnvironmentProvider([
            'HTTP_CACHE_PROVIDER' => 'cloudflare',
            'CLOUDFLARE_HTTP_CACHE_PURGE_BY_TAGS' => 'true',
        ]));

        $configuration = $factory->create();

        $this->assertInstanceOf(CloudflareHttpCache::class, $configuration);
        $this->assertTrue($configuration->purgeByTags);
    }

    public function testInvalidProviderThrows(): void
    {
        $factory = new HttpCacheConfigurationFactory(new HttpCacheTestEnvironmentProvider(['HTTP_CACHE_PROVIDER' => 'wat']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported HTTP_CACHE_PROVIDER');

        $factory->create();
    }
}

final class HttpCacheTestEnvironmentProvider extends SystemEnvironmentProvider
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
