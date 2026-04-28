<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteCollection;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RouteCollectionTest extends TestCase
{
    public function testAddRouteAndGetRoutesForMethod(): void
    {
        $collection = new RouteCollection();
        $route = new RegisteredRoute('/test', 'TestAction', []);
        
        $collection->addRoute('GET', $route);
        
        $routes = $collection->getRoutesForMethod('GET');
        $this->assertCount(1, $routes);
        $this->assertSame($route, $routes[0]);
        
        $this->assertEmpty($collection->getRoutesForMethod('POST'));
    }

    public function testMatch(): void
    {
        $collection = new RouteCollection();
        $route = new RegisteredRoute('#^/test/(?P<id>[^/]+)$#', 'TestAction', []);
        
        $collection->addRoute('GET', $route);
        
        $request = new ServerRequest('GET', '/test/123');
        $match = $collection->match($request);
        
        $this->assertNotNull($match);
        $this->assertEquals('TestAction', $match->actionClass);
        $this->assertEquals('123', $match->request->getAttribute('id'));
    }

    public function testMatchReturnsNullForNoMatch(): void
    {
        $collection = new RouteCollection();
        $route = new RegisteredRoute('#^/test$#', 'TestAction', []);
        
        $collection->addRoute('GET', $route);
        
        $request = new ServerRequest('POST', '/test');
        $match = $collection->match($request);
        
        $this->assertNull($match);
    }

    public function testGetAllowedMethodsForPath(): void
    {
        $collection = new RouteCollection();

        $collection->addRoute('GET', new RegisteredRoute('#^/test$#', 'GetTestAction', []));
        $collection->addRoute('PATCH', new RegisteredRoute('#^/test$#', 'PatchTestAction', []));

        $this->assertSame(['GET', 'PATCH'], $collection->getAllowedMethodsForPath('/test'));
        $this->assertSame([], $collection->getAllowedMethodsForPath('/missing'));
    }

    public function testGetAllRoutes(): void
    {
        $collection = new RouteCollection();
        $route1 = new RegisteredRoute('/test1', 'TestAction1', []);
        $route2 = new RegisteredRoute('/test2', 'TestAction2', []);
        
        $collection->addRoute('GET', $route1);
        $collection->addRoute('POST', $route2);
        
        $allRoutes = $collection->getAllRoutes();
        
        $this->assertArrayHasKey('GET', $allRoutes);
        $this->assertArrayHasKey('POST', $allRoutes);
        $this->assertCount(1, $allRoutes['GET']);
        $this->assertCount(1, $allRoutes['POST']);
        $this->assertSame($route1, $allRoutes['GET'][0]);
        $this->assertSame($route2, $allRoutes['POST'][0]);
    }

    public function testClear(): void
    {
        $collection = new RouteCollection();
        $route = new RegisteredRoute('/test', 'TestAction', []);
        
        $collection->addRoute('GET', $route);
        $this->assertNotEmpty($collection->getRoutesForMethod('GET'));
        
        $collection->clear();
        $this->assertEmpty($collection->getRoutesForMethod('GET'));
    }
}
