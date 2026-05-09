<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use InvalidArgumentException;
use PCF\Addendum\Http\Cache\HttpCacheContext;
use PCF\Addendum\Http\Cache\HttpCacheRuntime;
use PCF\Addendum\Http\Cache\NoneHttpCache;
use PCF\Addendum\Http\Cache\NoneHttpCacheBackendProvider;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\Middleware\AccessControl;
use PCF\Addendum\Http\Middleware\AccessControlFactory;
use PCF\Addendum\Http\Middleware\AccessControlGuardianCollection;
use PCF\Addendum\Http\Middleware\Cache;
use PCF\Addendum\Http\Middleware\CacheFactory;
use PCF\Addendum\Http\Middleware\CacheInvalidation;
use PCF\Addendum\Http\Middleware\CacheInvalidationFactory;
use PCF\Addendum\Http\Middleware\ClassAccessControlGuardianDefinition;
use PCF\Addendum\Http\Middleware\Dummy;
use PCF\Addendum\Http\Middleware\DummyFactory;
use PCF\Addendum\Http\Middleware\HttpCache;
use PCF\Addendum\Http\Middleware\HttpCacheFactory;
use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use PCF\Addendum\Http\Middleware\ValidateRequestAttributeFactory;
use PCF\Addendum\Http\MiddlewareOptions;
use PHPUnit\Framework\TestCase;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreatesMiddlewareFromOptions(): void
    {
        self::assertInstanceOf(AccessControl::class, new AccessControlFactory()->create(new MiddlewareOptions([
            'accessControlGuardians' => new AccessControlGuardianCollection([
                new ClassAccessControlGuardianDefinition(AccessControlFactoryFixtureGuardian::class),
            ]),
        ])));
        self::assertInstanceOf(ValidateRequestAttribute::class, new ValidateRequestAttributeFactory()->create(new MiddlewareOptions([
            'validationRules' => [],
        ])));
        self::assertInstanceOf(Dummy::class, new DummyFactory()->create(new MiddlewareOptions(['header' => 'ok'])));
        self::assertInstanceOf(Cache::class, new CacheFactory()->create(new MiddlewareOptions([
            'ttl' => 30,
            'key' => 'cache-key',
            'session' => true,
            'params' => ['id'],
        ])));
        self::assertInstanceOf(CacheInvalidation::class, new CacheInvalidationFactory()->create(new MiddlewareOptions([
            'key' => 'cache-key',
            'session' => true,
            'params' => ['id'],
        ])));
    }

    public function testCreatesHttpCacheWhenRequiredOptionsArePresent(): void
    {
        $middleware = new HttpCacheFactory()->create(new MiddlewareOptions([
            'resourcePolicies' => ResourcePolicyCollection::fromArray([]),
            'httpCacheRuntime' => new HttpCacheRuntime(
                new NoneHttpCache(new HttpCacheContext()),
                new NoneHttpCacheBackendProvider()
            ),
        ]));

        self::assertInstanceOf(HttpCache::class, $middleware);
    }

    public function testHttpCacheFactoryRequiresResourcePolicies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTP cache resource policies are required');

        new HttpCacheFactory()->create(new MiddlewareOptions([
            'httpCacheRuntime' => new HttpCacheRuntime(
                new NoneHttpCache(new HttpCacheContext()),
                new NoneHttpCacheBackendProvider()
            ),
        ]));
    }

    public function testHttpCacheFactoryRequiresRuntime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HTTP cache runtime is required');

        new HttpCacheFactory()->create(new MiddlewareOptions([
            'resourcePolicies' => ResourcePolicyCollection::fromArray([]),
        ]));
    }
}

final class AccessControlFactoryFixtureGuardian
{
}
