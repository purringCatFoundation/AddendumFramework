<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\RouteMiddlewareCollection;
use ReflectionClass;

interface MiddlewareProviderInterface
{
    /**
     * Provides middleware for the given action class
     *
     * @param ReflectionClass $actionClass
     */
    public function provide(ReflectionClass $actionClass): RouteMiddlewareCollection;
}
