<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteCollection;
use PCF\Addendum\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchesRequestThroughRouteCollection(): void
    {
        $router = new Router($this->routes());

        $match = $router->match(new ServerRequest('GET', '/users/123'));

        self::assertNotNull($match);
        self::assertSame('GetUserAction', $match->actionClass);
        self::assertSame('123', $match->request->getAttribute('userUuid'));
    }

    public function testReturnsNullWhenNoRouteMatches(): void
    {
        $router = new Router($this->routes());

        self::assertNull($router->match(new ServerRequest('POST', '/missing')));
    }

    public function testReturnsAllowedMethodsForPath(): void
    {
        $router = new Router($this->routes());

        self::assertSame(['GET', 'PATCH'], $router->getAllowedMethodsForPath('/users/123')->toArray());
        self::assertSame([], $router->getAllowedMethodsForPath('/missing')->toArray());
    }

    public function testReturnsUnderlyingRouteCollection(): void
    {
        $routes = $this->routes();
        $router = new Router($routes);

        self::assertSame($routes, $router->getRoutes());
    }

    private function routes(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->addRoute('GET', new RegisteredRoute(
            '#^/users/(?P<userUuid>[^/]+)$#',
            'GetUserAction',
            [],
            $this->resourcePolicies(),
            '/users/:userUuid'
        ));
        $routes->addRoute('PATCH', new RegisteredRoute(
            '#^/users/(?P<userUuid>[^/]+)$#',
            'PatchUserAction',
            [],
            $this->resourcePolicies(),
            '/users/:userUuid'
        ));

        return $routes;
    }

    private function resourcePolicies(): ResourcePolicyCollection
    {
        return new ResourcePolicyCollection([
            new ResourcePolicy(mode: HttpCacheMode::PRIVATE),
        ]);
    }
}
