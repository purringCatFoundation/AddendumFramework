<?php

declare(strict_types=1);

namespace CitiesRpg\Tests\Middleware;

use PCF\Addendum\Http\Middleware\CacheInvalidation;
use PCF\Addendum\Http\Middleware\CacheInvalidationFactory;
use PCF\Addendum\Http\MiddlewareOptions;
use PHPUnit\Framework\TestCase;

final class CacheInvalidationFactoryTest extends TestCase
{
    public function testCreatesMiddleware(): void
    {
        $middleware = new CacheInvalidationFactory()->create(MiddlewareOptions::fromArray(['params' => ['foo']]));
        $this->assertInstanceOf(CacheInvalidation::class, $middleware);
    }
}
