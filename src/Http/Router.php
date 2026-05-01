<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\Routing\ActionScanner;
use PCF\Addendum\Http\Routing\MiddlewareStackBuilder;
use PCF\Addendum\Http\Routing\RoutePatternCompiler;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionAttribute;

/**
 * Router - Discovers and registers routes from Action classes
 *
 * Responsibilities:
 * - Orchestrates route registration
 * - Delegates scanning to ActionScanner
 * - Delegates middleware building to MiddlewareStackBuilder
 * - Delegates pattern compilation to RoutePatternCompiler
 */
class Router
{
    private RouteCollection $routes;
    /** @var ActionScanner[] */
    private readonly array $scanners;
    private readonly MiddlewareStackBuilder $middlewareBuilder;
    private readonly RoutePatternCompiler $patternCompiler;

    /**
     * @param ActionScanner[] $scanners Array of ActionScanner instances to scan for routes
     * @param MiddlewareStackBuilder|null $middlewareBuilder
     * @param RoutePatternCompiler|null $patternCompiler
     */
    public function __construct(
        array $scanners,
        ?MiddlewareStackBuilder $middlewareBuilder = null,
        ?RoutePatternCompiler $patternCompiler = null
    ) {
        $this->scanners = $scanners;
        $this->middlewareBuilder = $middlewareBuilder ?? new MiddlewareStackBuilder();
        $this->patternCompiler = $patternCompiler ?? new RoutePatternCompiler();

        $this->routes = new RouteCollection();
        $this->registerActions();
    }

    /**
     * Scans for Action classes and registers their routes
     */
    private function registerActions(): void
    {
        foreach ($this->scanners as $scanner) {
            $actions = $scanner->scanActions();

            foreach ($actions as $actionReflection) {
                $routeAttributes = $actionReflection->getAttributes(Route::class);

                if (empty($routeAttributes)) {
                    continue;
                }

                $actionClass = $actionReflection->getName();
                $middlewares = $this->middlewareBuilder->buildStack($actionReflection);
                $resourcePolicies = ResourcePolicyCollection::fromArray(array_map(
                    static fn(ReflectionAttribute $attribute): ResourcePolicy => $attribute->newInstance(),
                    $actionReflection->getAttributes(ResourcePolicy::class)
                ));

                foreach ($routeAttributes as $routeAttribute) {
                    /** @var Route $route */
                    $route = $routeAttribute->newInstance();
                    $pattern = $this->patternCompiler->compile($route);

                    $registeredRoute = new RegisteredRoute($pattern, $actionClass, $middlewares, $resourcePolicies);
                    $this->routes->addRoute($route->method, $registeredRoute);
                }
            }
        }
    }

    /**
     * Matches a request to a registered route
     *
     * @param ServerRequestInterface $request
     * @return RouteMatch|null RouteMatch if found, null if no route matches
     */
    public function match(ServerRequestInterface $request): ?RouteMatch
    {
        return $this->routes->match($request);
    }

    /**
     * @return list<string>
     */
    public function getAllowedMethodsForPath(string $path): array
    {
        return $this->routes->getAllowedMethodsForPath($path);
    }

    /**
     * Returns the route collection
     *
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}
