<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Http\Routing\MiddlewareProviderInterface;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use ReflectionClass;

class ValidateRequestMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): array
    {
        $hasValidateRequestAttributes = !empty($actionClass->getAttributes(ValidateRequest::class));

        if (!$hasValidateRequestAttributes) {
            return [];
        }

        return [
            new RouteMiddleware(
                ValidateRequestAttribute::class,
                new MiddlewareOptions()->withActionClass($actionClass->getName())
            )
        ];
    }
}
