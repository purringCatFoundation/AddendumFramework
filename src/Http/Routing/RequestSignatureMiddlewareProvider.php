<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Attribute\AccessControl as AccessControlAttribute;
use PCF\Addendum\Attribute\Middleware as MiddlewareAttribute;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Middleware\RequestSignature;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddlewareCollection;
use PCF\Addendum\Http\RouteMiddleware;
use ReflectionClass;

/**
 * Provides RequestSignature middleware for authenticated endpoints.
 *
 * Request signature verification is applied when Auth middleware is present to ensure:
 * - Request integrity (tampering protection)
 * - Replay attack prevention (timestamp validation)
 * - Device binding (fingerprint verification)
 */
class RequestSignatureMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): RouteMiddlewareCollection
    {
        if (!$this->requiresAuth($actionClass)) {
            return RouteMiddlewareCollection::empty();
        }

        return new RouteMiddlewareCollection([
            new RouteMiddleware(
                RequestSignature::class,
                new MiddlewareOptions()
            )
        ]);
    }

    private function requiresAuth(ReflectionClass $actionClass): bool
    {
        if ($actionClass->getAttributes(AccessControlAttribute::class) !== []) {
            return true;
        }

        foreach ($actionClass->getAttributes(MiddlewareAttribute::class) as $middlewareAttribute) {
            if ($middlewareAttribute->newInstance()->middlewareClass === Auth::class) {
                return true;
            }
        }

        return false;
    }
}
