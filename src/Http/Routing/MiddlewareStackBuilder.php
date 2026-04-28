<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\Middleware\AccessControl;
use PCF\Addendum\Http\Middleware\Auth;
use PCF\Addendum\Http\Middleware\RateLimitMiddleware;
use PCF\Addendum\Http\Middleware\RequestSignature;
use PCF\Addendum\Http\Middleware\ValidateRequestAttribute;
use ReflectionClass;

class MiddlewareStackBuilder
{
    private const array MIDDLEWARE_PRIORITY = [
        Auth::class => 10,
        RequestSignature::class => 20,
        RateLimitMiddleware::class => 30,
        ValidateRequestAttribute::class => 40,
        AccessControl::class => 50,
    ];

    /** @var list<MiddlewareProviderInterface> */
    private array $providers = [];

    public function __construct()
    {
        $this->providers = [
            new CustomMiddlewareProvider(),
            new RequestSignatureMiddlewareProvider(),
            new RateLimitMiddlewareProvider(),
            new ValidateRequestMiddlewareProvider(),
            new AccessControlMiddlewareProvider(),
        ];
    }

    /**
     * Builds the middleware stack for the given action class
     *
     * Special handling: RequestSignature middleware is automatically positioned:
     * - After Auth middleware if Auth is present
     * - At the beginning of the stack if Auth is not present
     *
     * @param ReflectionClass $actionClass
     * @return list<RouteMiddleware>
     */
    public function buildStack(ReflectionClass $actionClass): array
    {
        $middlewares = [];

        // Collect middlewares from all providers
        foreach ($this->providers as $provider) {
            $providedMiddlewares = $provider->provide($actionClass);
            $middlewares = array_merge($middlewares, $providedMiddlewares);
        }

        $middlewares = $this->deduplicateInfrastructureMiddlewares($middlewares);
        $middlewares = $this->sortByPriority($middlewares);

        return $middlewares;
    }

    /**
     * @param list<RouteMiddleware> $middlewares
     * @return list<RouteMiddleware>
     */
    private function deduplicateInfrastructureMiddlewares(array $middlewares): array
    {
        $seen = [];
        $deduplicated = [];

        foreach ($middlewares as $middleware) {
            $middlewareClass = $middleware->getClass();

            if (isset(self::MIDDLEWARE_PRIORITY[$middlewareClass])) {
                if (isset($seen[$middlewareClass])) {
                    continue;
                }

                $seen[$middlewareClass] = true;
            }

            $deduplicated[] = $middleware;
        }

        return $deduplicated;
    }

    /**
     * @param list<RouteMiddleware> $middlewares
     * @return list<RouteMiddleware>
     */
    private function sortByPriority(array $middlewares): array
    {
        $indexed = [];

        foreach ($middlewares as $index => $middleware) {
            $indexed[] = [$index, $middleware];
        }

        usort(
            $indexed,
            static function (array $left, array $right): int {
                $leftPriority = self::MIDDLEWARE_PRIORITY[$left[1]->getClass()] ?? 100;
                $rightPriority = self::MIDDLEWARE_PRIORITY[$right[1]->getClass()] ?? 100;

                return $leftPriority <=> $rightPriority ?: $left[0] <=> $right[0];
            }
        );

        return array_map(static fn(array $item): RouteMiddleware => $item[1], $indexed);
    }
}
