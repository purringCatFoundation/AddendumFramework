<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http;

use Psr\Http\Message\ServerRequestInterface;

class RouteCollection
{
    /**
     * @var array<string, list<RegisteredRoute>>
     */
    private array $routes = [];

    public function addRoute(string $method, RegisteredRoute $route): void
    {
        $this->routes[$method][] = $route;
    }

    /**
     * @return list<RegisteredRoute>
     */
    public function getRoutesForMethod(string $method): array
    {
        return $this->routes[$method] ?? [];
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