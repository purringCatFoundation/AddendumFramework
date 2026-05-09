<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Attribute\AccessControl as AccessControlAttribute;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Middleware\AccessControlGuardianCollection;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddlewareCollection;
use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\Middleware\AccessControl;
use ReflectionClass;

class AccessControlMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): RouteMiddlewareCollection
    {
        $attributes = $actionClass->getAttributes(AccessControlAttribute::class);

        if ($attributes === []) {
            return RouteMiddlewareCollection::empty();
        }

        $guardians = new AccessControlGuardianCollection();

        foreach ($attributes as $attribute) {
            $guardians->add($attribute->newInstance()->getGuardianDefinition());
        }

        return new RouteMiddlewareCollection([
            new RouteMiddleware(
                Auth::class,
                new MiddlewareOptions()
            ),
            new RouteMiddleware(
                AccessControl::class,
                new MiddlewareOptions(additionalData: ['accessControlGuardians' => $guardians])
            )
        ]);
    }
}
