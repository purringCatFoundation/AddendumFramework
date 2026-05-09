<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use Ds\Vector;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteCollection;
use ReflectionAttribute;

final readonly class RouteCollectionBuilder
{
    /** @var Vector<ActionScanner> */
    private Vector $scanners;

    /**
     * @param iterable<ActionScanner> $scanners
     */
    public function __construct(
        iterable $scanners,
        private MiddlewareStackBuilder $middlewareBuilder,
        private RoutePatternCompiler $patternCompiler
    ) {
        $this->scanners = $scanners instanceof Vector ? $scanners->copy() : new Vector($scanners);
    }

    public function build(): RouteCollection
    {
        $routes = new RouteCollection();

        foreach ($this->scanners as $scanner) {
            foreach ($scanner->scanActions() as $actionReflection) {
                $routeAttributes = $actionReflection->getAttributes(Route::class);

                if ($routeAttributes === []) {
                    continue;
                }

                $actionClass = $actionReflection->getName();
                $middlewares = $this->middlewareBuilder->buildStack($actionReflection);
                $resourcePolicies = $this->resourcePolicies($actionReflection->getAttributes(ResourcePolicy::class));

                foreach ($routeAttributes as $routeAttribute) {
                    /** @var Route $route */
                    $route = $routeAttribute->newInstance();

                    $routes->addRoute(
                        $route->method,
                        new RegisteredRoute(
                            pattern: $this->patternCompiler->compile($route),
                            actionClass: $actionClass,
                            middlewares: $middlewares,
                            resourcePolicies: $resourcePolicies,
                            path: $route->path
                        )
                    );
                }
            }
        }

        return $routes;
    }

    /**
     * @param list<ReflectionAttribute> $attributes
     */
    private function resourcePolicies(array $attributes): ResourcePolicyCollection
    {
        $policies = array_map(
            static fn(ReflectionAttribute $attribute): ResourcePolicy => $attribute->newInstance(),
            $attributes
        );

        if ($policies === []) {
            $policies[] = new ResourcePolicy(mode: HttpCacheMode::PRIVATE);
        }

        return new ResourcePolicyCollection($policies);
    }
}
