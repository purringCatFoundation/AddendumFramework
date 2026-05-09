<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Http\Routing\MiddlewareProviderInterface;
use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddlewareCollection;
use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use PCF\Addendum\Validation\RequestValidationRuleCollection;
use ReflectionClass;

class ValidateRequestMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): RouteMiddlewareCollection
    {
        $attributes = $actionClass->getAttributes(ValidateRequest::class);

        if ($attributes === []) {
            return RouteMiddlewareCollection::empty();
        }

        return new RouteMiddlewareCollection([
            new RouteMiddleware(
                ValidateRequestAttribute::class,
                new MiddlewareOptions(additionalData: [
                    'validationRules' => new RequestValidationRuleCollection(array_map(
                        static fn(\ReflectionAttribute $attribute) => $attribute->newInstance()->toRule(),
                        $attributes
                    )),
                ])
            )
        ]);
    }
}
