<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteMiddleware;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RegisteredRouteTest extends TestCase
{
    public function testConstructor(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions('TestAction')
        );
        
        $route = new RegisteredRoute('/test', 'TestAction', [$middleware]);
        
        $this->assertEquals('/test', $route->pattern);
        $this->assertEquals('TestAction', $route->actionClass);
        $this->assertEquals([$middleware], $route->middlewares);
    }

    public function testMiddlewareAccess(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions('TestAction', ['key' => 'value'])
        );
        
        $route = new RegisteredRoute('/test', 'TestAction', [$middleware]);
        
        $this->assertEquals('/test', $route->pattern);
        $this->assertEquals('TestAction', $route->actionClass);
        $this->assertCount(1, $route->middlewares);
        $this->assertEquals('TestMiddleware', $route->middlewares[0]->getClass());
        $this->assertEquals('TestAction', $route->middlewares[0]->getOptions()->actionClass);
    }

    public function testMatches(): void
    {
        $route = new RegisteredRoute('#^/test/([^/]+)$#', 'TestAction', []);
        
        $this->assertNotNull($route->matches('/test/value'));
        $this->assertNull($route->matches('/other/path'));
    }

    public function testCreateMatchResult(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions('', ['key' => 'value'])
        );
        
        $route = new RegisteredRoute('#^/test/(?P<id>[^/]+)$#', 'TestAction', [$middleware]);
        $request = new ServerRequest('GET', '/test/123');
        
        $match = $route->createMatchResult($request);
        
        $this->assertEquals('TestAction', $match->actionClass);
        $this->assertEquals('123', $match->request->getAttribute('id'));
        $this->assertCount(1, $match->middlewares);
        $this->assertEquals('TestAction', $match->middlewares[0]->getOptions()->actionClass);
    }
}