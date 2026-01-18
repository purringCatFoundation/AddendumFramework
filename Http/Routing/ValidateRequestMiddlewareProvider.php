<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Routing;

use Pradzikowski\Framework\Http\Routing\MiddlewareProviderInterface;
use Pradzikowski\Framework\Attribute\ValidateRequest;
use Pradzikowski\Framework\Http\MiddlewareOptions;
use Pradzikowski\Framework\Http\RouteMiddleware;
use Pradzikowski\Framework\Http\Middleware\ValidateRequestAttribute;
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
                (new MiddlewareOptions())->withActionClass($actionClass->getName())
            )
        ];
    }
}
