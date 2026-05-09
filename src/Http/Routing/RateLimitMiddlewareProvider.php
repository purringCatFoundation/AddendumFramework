<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Attribute\RateLimit;
use PCF\Addendum\Http\Middleware\RateLimitMiddleware;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddlewareCollection;
use PCF\Addendum\Http\RouteMiddleware;
use ReflectionClass;

final class RateLimitMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): RouteMiddlewareCollection
    {
        $attributes = $actionClass->getAttributes(RateLimit::class);
        $rateLimit = $attributes !== [] ? $attributes[0]->newInstance() : null;

        return new RouteMiddlewareCollection([
            new RouteMiddleware(
                RateLimitMiddleware::class,
                new MiddlewareOptions(additionalData: [
                    'rateLimit' => $rateLimit,
                ])
            ),
        ]);
    }
}
