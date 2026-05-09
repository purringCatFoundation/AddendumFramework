<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Http\Routing\MiddlewareProviderInterface;
use PCF\Addendum\Attribute\Middleware;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddlewareCollection;
use PCF\Addendum\Http\RouteMiddleware;
use ReflectionClass;

class CustomMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): RouteMiddlewareCollection
    {
        $middlewares = new RouteMiddlewareCollection();

        foreach ($actionClass->getAttributes(Middleware::class) as $middlewareAttribute) {
            $instance = $middlewareAttribute->newInstance();
            $middlewareClass = $instance->middlewareClass;

            if (!class_exists($middlewareClass)) {
                continue;
            }

            $middleware = new RouteMiddleware(
                $middlewareClass,
                new MiddlewareOptions()
            );

            if (!empty($instance->options)) {
                $middleware = $middleware->addOptions($instance->options);
            }

            $middlewares->add($middleware);
        }

        return $middlewares;
    }
}
