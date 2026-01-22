<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Http\Middleware\RequestSignature;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddleware;
use ReflectionClass;

/**
 * Provides RequestSignature middleware for ALL endpoints
 *
 * Request signature verification is applied to every action to ensure:
 * - Request integrity (tampering protection)
 * - Replay attack prevention (timestamp validation)
 * - Device binding (fingerprint verification)
 */
class RequestSignatureMiddlewareProvider implements MiddlewareProviderInterface
{
    public function provide(ReflectionClass $actionClass): array
    {
        return [
            new RouteMiddleware(
                RequestSignature::class,
                new MiddlewareOptions()
            )
        ];
    }
}
