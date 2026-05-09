<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http\Cache;

use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Middleware\RateLimitMiddleware;
use PCF\Addendum\Http\Middleware\RequestSignature;
use PCF\Addendum\Http\Routing\MiddlewareStackBuilder;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class HttpCacheMiddlewareStackTest extends TestCase
{
    public function testHttpCacheIsNotPartOfRouteMiddlewareStack(): void
    {
        $builder = new MiddlewareStackBuilder();

        $middlewares = $builder->buildStack(new ReflectionClass(HttpCacheStackFixtureAction::class));

        $this->assertSame([
            Auth::class,
            RequestSignature::class,
            RateLimitMiddleware::class,
        ], array_map(static fn($middleware): string => $middleware->getClass(), $middlewares->all()->toArray()));
    }
}

#[Middleware(Auth::class)]
#[ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'article')]
final class HttpCacheStackFixtureAction
{
}
