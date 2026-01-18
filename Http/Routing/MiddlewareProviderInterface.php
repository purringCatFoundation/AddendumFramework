<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Routing;

use Pradzikowski\Framework\Http\RouteMiddleware;
use ReflectionClass;

interface MiddlewareProviderInterface
{
    /**
     * Provides middleware for the given action class
     *
     * @param ReflectionClass $actionClass
     * @return list<RouteMiddleware>
     */
    public function provide(ReflectionClass $actionClass): array;
}
