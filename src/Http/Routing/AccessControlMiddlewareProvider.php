<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Attribute\AccessControl as AccessControlAttribute;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\Middleware\AccessControl;
use ReflectionClass;

class AccessControlMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): array
    {
        $hasAccessControlAttributes = !empty($actionClass->getAttributes(AccessControlAttribute::class));

        if (!$hasAccessControlAttributes) {
            return [];
        }

        return [
            new RouteMiddleware(
                AccessControl::class,
                new MiddlewareOptions()
            )
        ];
    }
}