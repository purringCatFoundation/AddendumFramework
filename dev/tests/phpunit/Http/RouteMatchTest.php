<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMatch;
use PCF\Addendum\Http\RouteMiddleware;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RouteMatchTest extends TestCase
{
    public function testConstructor(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions('TestAction', ['key' => 'value'])
        );
        $request = new ServerRequest('GET', '/test');
        
        $match = new RouteMatch('TestAction', [$middleware], $request);
        
        $this->assertEquals('TestAction', $match->actionClass);
        $this->assertEquals([$middleware], $match->middlewares);
        $this->assertSame($request, $match->request);
    }

    public function testMiddlewareAccess(): void
    {
        $middleware = new RouteMiddleware(
            'TestMiddleware',
            new MiddlewareOptions('TestAction', ['key' => 'value'])
        );
        $request = new ServerRequest('GET', '/test');
        
        $match = new RouteMatch('TestAction', [$middleware], $request);
        
        $this->assertCount(1, $match->middlewares);
        $this->assertEquals('TestMiddleware', $match->middlewares[0]->getClass());
        $this->assertInstanceOf(MiddlewareOptions::class, $match->middlewares[0]->getOptions());
        $this->assertEquals('TestAction', $match->middlewares[0]->getOptions()->actionClass);
    }
}