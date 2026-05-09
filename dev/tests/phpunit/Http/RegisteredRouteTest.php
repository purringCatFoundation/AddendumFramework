<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteMiddlewareCollection;
use PCF\Addendum\Http\RouteMiddleware;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RegisteredRouteTest extends TestCase
{
    public function testConstructor(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions()
        );
        
        $policies = $this->resourcePolicies();
        $route = new RegisteredRoute('/test', 'TestAction', [$middleware], $policies);
        
        $this->assertEquals('/test', $route->pattern);
        $this->assertEquals('TestAction', $route->actionClass);
        $this->assertEquals(new RouteMiddlewareCollection([$middleware]), $route->middlewares);
        $this->assertSame($policies, $route->resourcePolicies);
    }

    public function testMiddlewareAccess(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions(['key' => 'value'])
        );
        
        $route = new RegisteredRoute('/test', 'TestAction', [$middleware], $this->resourcePolicies());
        
        $this->assertEquals('/test', $route->pattern);
        $this->assertEquals('TestAction', $route->actionClass);
        $this->assertCount(1, $route->middlewares);
        $this->assertEquals('TestMiddleware', $route->middlewares[0]->getClass());
        $this->assertEquals(['key' => 'value'], $route->middlewares[0]->getOptions()->additionalData->toArray());
    }

    public function testMatches(): void
    {
        $route = new RegisteredRoute('#^/test/([^/]+)$#', 'TestAction', [], $this->resourcePolicies());
        
        $this->assertNotNull($route->matches('/test/value'));
        $this->assertNull($route->matches('/other/path'));
    }

    public function testPathFallsBackToReadableNamedPattern(): void
    {
        $route = new RegisteredRoute('#^/test/(?P<id>[^/]+)$#', 'TestAction', [], $this->resourcePolicies());

        $this->assertEquals('/test/:id', $route->path);
    }

    public function testCreateMatchResult(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions(['key' => 'value'])
        );
        
        $route = new RegisteredRoute('#^/test/(?P<id>[^/]+)$#', 'TestAction', [$middleware], $this->resourcePolicies());
        $request = new ServerRequest('GET', '/test/123');
        
        $match = $route->createMatchResult($request);
        
        $this->assertEquals('TestAction', $match->actionClass);
        $this->assertEquals('123', $match->request->getAttribute('id'));
        $this->assertEquals('TestAction', $match->request->getAttribute('action_class'));
        $this->assertCount(1, $match->middlewares);
        $this->assertEquals(['key' => 'value'], $match->middlewares[0]->getOptions()->additionalData->toArray());
    }

    public function testCreateMatchResultIncludesResourcePolicies(): void
    {
        $policies = $this->resourcePolicies();
        $route = new RegisteredRoute('#^/test$#', 'TestAction', [], $policies);
        $request = new ServerRequest('GET', '/test');

        $match = $route->createMatchResult($request);

        $this->assertSame($policies, $match->resourcePolicies);
    }

    private function resourcePolicies(): ResourcePolicyCollection
    {
        return new ResourcePolicyCollection([
            new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'test'),
        ]);
    }
}
