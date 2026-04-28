<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use Psr\Http\Message\ServerRequestInterface;

class RouteCollection
{
    /**
     * @var array<string, list<RegisteredRoute>>
     */
    private array $routes = [];

    public function addRoute(string $method, RegisteredRoute $route): void
    {
        $method = strtoupper($method);
        $this->routes[$method][] = $route;
    }

    /**
     * @return list<RegisteredRoute>
     */
    public function getRoutesForMethod(string $method): array
    {
        $method = strtoupper($method);
        return $this->routes[$method] ?? [];
    }

    /**
     * @return list<string>
     */
    public function getAllowedMethodsForPath(string $path): array
    {
        $allowedMethods = [];

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                if ($route->matches($path) !== null) {
                    $allowedMethods[] = $method;
                    break;
                }
            }
        }

        return array_values(array_unique($allowedMethods));
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


    public function getAllRoutes(): array
    {
        return $this->routes;
    }

    public function clear(): void
    {
        $this->routes = [];
    }
}
