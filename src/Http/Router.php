<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use Ds\Vector;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Runtime router over a pre-built route collection.
 */
class Router
{
    public function __construct(
        private RouteCollection $routes
    ) {
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

    /** @return Vector<string> */
    public function getAllowedMethodsForPath(string $path): Vector
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
