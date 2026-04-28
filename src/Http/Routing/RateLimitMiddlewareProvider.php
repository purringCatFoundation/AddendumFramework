<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Http\Middleware\RateLimitMiddleware;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddleware;
use ReflectionClass;

final class RateLimitMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): array
    {
        return [
            new RouteMiddleware(
                RateLimitMiddleware::class,
                new MiddlewareOptions()
            ),
        ];
    }
}
