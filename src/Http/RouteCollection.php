<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use Ds\Map;
use Ds\Set;
use Ds\Vector;
use Psr\Http\Message\ServerRequestInterface;

class RouteCollection
{
    /** @var Map<string, RegisteredRouteCollection> */
    private Map $routes;

    public function __construct()
    {
        $this->routes = new Map();
    }

    public function addRoute(string $method, RegisteredRoute $route): void
    {
        $method = strtoupper($method);

        if (!$this->routes->hasKey($method)) {
            $this->routes->put($method, new RegisteredRouteCollection());
        }

        $this->routes->get($method)->add($route);
    }

    public function getRoutesForMethod(string $method): RegisteredRouteCollection
    {
        $method = strtoupper($method);

        return $this->routes->hasKey($method)
            ? $this->routes->get($method)
            : RegisteredRouteCollection::empty();
    }

    /** @return Vector<string> */
    public function getAllowedMethodsForPath(string $path): Vector
    {
        $allowedMethods = new Set();

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                if ($route->matches($path) !== null) {
                    $allowedMethods->add($method);
                    break;
                }
            }
        }

        return new Vector($allowedMethods);
    }

    public function match(ServerRequestInterface $request): ?RouteMatch
    {
        $methodRoutes = $this->getRoutesForMethod($request->getMethod());
        $path = $request->getUri()->getPath();

        foreach ($methodRoutes as $route) {
            if ($route->matches($path) !== null) {
                return $route->createMatchResult($request);
            }
        }

        return null;
    }


    /** @return Map<string, RegisteredRouteCollection> */
    public function getAllRoutes(): Map
    {
        return $this->routes->copy();
    }

    public function clear(): void
    {
        $this->routes = new Map();
    }
}
