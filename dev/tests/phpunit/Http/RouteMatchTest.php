<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Attribute\ResourcePolicy;
use PCF\Addendum\Http\Cache\HttpCacheMode;
use PCF\Addendum\Http\Cache\ResourcePolicyCollection;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMatch;
use PCF\Addendum\Http\RouteMiddlewareCollection;
use PCF\Addendum\Http\RouteMiddleware;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RouteMatchTest extends TestCase
{
    public function testConstructor(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions(['key' => 'value'])
        );
        $request = new ServerRequest('GET', '/test');
        
        $policies = $this->resourcePolicies();
        $match = new RouteMatch('TestAction', new RouteMiddlewareCollection([$middleware]), $request, $policies);
        
        $this->assertEquals('TestAction', $match->actionClass);
        $this->assertEquals(new RouteMiddlewareCollection([$middleware]), $match->middlewares);
        $this->assertSame($request, $match->request);
        $this->assertSame($policies, $match->resourcePolicies);
    }

    public function testMiddlewareAccess(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions(['key' => 'value'])
        );
        $request = new ServerRequest('GET', '/test');
        
        $match = new RouteMatch('TestAction', new RouteMiddlewareCollection([$middleware]), $request, $this->resourcePolicies());
        
        $this->assertCount(1, $match->middlewares);
        $this->assertEquals('TestMiddleware', $match->middlewares[0]->getClass());
        $this->assertInstanceOf(MiddlewareOptions::class, $match->middlewares[0]->getOptions());
        $this->assertEquals(['key' => 'value'], $match->middlewares[0]->getOptions()->additionalData->toArray());
    }

    public function testResourcePoliciesAccess(): void
    {
        $request = new ServerRequest('GET', '/test');
        $policies = $this->resourcePolicies();

        $match = new RouteMatch('TestAction', RouteMiddlewareCollection::empty(), $request, $policies);

        $this->assertSame($policies, $match->resourcePolicies);
    }

    private function resourcePolicies(): ResourcePolicyCollection
    {
        return new ResourcePolicyCollection([
            new ResourcePolicy(mode: HttpCacheMode::PUBLIC, maxAge: 60, resource: 'test'),
        ]);
    }
}
