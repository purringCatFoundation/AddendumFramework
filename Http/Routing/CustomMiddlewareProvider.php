<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Routing;

use Pradzikowski\Framework\Http\Routing\MiddlewareProviderInterface;
use Pradzikowski\Framework\Attribute\Middleware;
use Pradzikowski\Framework\Http\MiddlewareOptions;
use Pradzikowski\Framework\Http\RouteMiddleware;
use ReflectionClass;

class CustomMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): array
    {
        $middlewares = [];

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

            $middlewares[] = $middleware;
        }

        return $middlewares;
    }
}
