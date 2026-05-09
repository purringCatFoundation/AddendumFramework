<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use Ds\Vector;
use PCF\Addendum\Http\RouteMiddleware;
use PCF\Addendum\Http\RouteMiddlewareCollection;
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

    /** @var Vector<MiddlewareProviderInterface> */
    private Vector $providers;

    public function __construct()
    {
        $this->providers = new Vector([
            new CustomMiddlewareProvider(),
            new RequestSignatureMiddlewareProvider(),
            new RateLimitMiddlewareProvider(),
            new ValidateRequestMiddlewareProvider(),
            new AccessControlMiddlewareProvider(),
        ]);
    }

    /**
     * Builds the middleware stack for the given action class
     *
     * Infrastructure middleware is sorted into a stable order so security and validation
     * run before response decoration middleware.
     *
     * @param ReflectionClass $actionClass
     */
    public function buildStack(ReflectionClass $actionClass): RouteMiddlewareCollection
    {
        $middlewares = RouteMiddlewareCollection::empty();

        // Collect middlewares from all providers
        foreach ($this->providers as $provider) {
            $middlewares = $middlewares->merge($provider->provide($actionClass));
        }

        $middlewares = $this->deduplicateInfrastructureMiddlewares($middlewares);
        $middlewares = $this->sortByPriority($middlewares);

        return $middlewares;
    }

    /**
     */
    private function deduplicateInfrastructureMiddlewares(RouteMiddlewareCollection $middlewares): RouteMiddlewareCollection
    {
        $seen = [];
        $deduplicated = new RouteMiddlewareCollection();

        foreach ($middlewares as $middleware) {
            $middlewareClass = $middleware->getClass();

            if (isset(self::MIDDLEWARE_PRIORITY[$middlewareClass])) {
                if (isset($seen[$middlewareClass])) {
                    continue;
                }

                $seen[$middlewareClass] = true;
            }

            $deduplicated->add($middleware);
        }

        return $deduplicated;
    }

    private function sortByPriority(RouteMiddlewareCollection $middlewares): RouteMiddlewareCollection
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

        return new RouteMiddlewareCollection(array_map(static fn(array $item): RouteMiddleware => $item[1], $indexed));
    }
}
