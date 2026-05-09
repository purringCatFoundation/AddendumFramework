<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Http\RouteMiddleware;
use PHPUnit\Framework\TestCase;

class RouteMiddlewareTest extends TestCase
{
    public function testRouteMiddlewareImplementsInterface(): void
    {
        $middleware = new RouteMiddleware('TestMiddleware', new MiddlewareOptions());
        
        $this->assertInstanceOf(RouteMiddleware::class, $middleware);
    }

    public function testInterfaceMethodsWork(): void
    {
        $middleware = new RouteMiddleware('TestMiddleware', new MiddlewareOptions());
        
        // Test all interface methods
        $this->assertEquals('TestMiddleware', $middleware->getClass());
        $this->assertInstanceOf(MiddlewareOptions::class, $middleware->getOptions());
        
        $withOption = $middleware->addOption('key', 'value');
        $this->assertInstanceOf(RouteMiddleware::class, $withOption);
        
        $withOptions = $middleware->addOptions(['key1' => 'value1']);
        $this->assertInstanceOf(RouteMiddleware::class, $withOptions);
    }

    public function testFluentInterfaceReturnsSameInstance(): void
    {
        $middleware = new RouteMiddleware('TestMiddleware', new MiddlewareOptions());
        
        $result = $middleware
            ->addOption('timeout', 30)
            ->addOptions(['retries' => 3]);
        
        $this->assertInstanceOf(RouteMiddleware::class, $result);
        $this->assertInstanceOf(RouteMiddleware::class, $result);
        $this->assertSame($middleware, $result); // Same instance
        
        // Verify the fluent operations worked on the original instance
        $options = $middleware->getOptions();
        $this->assertEquals(30, $options->get('timeout'));
        $this->assertEquals(3, $options->get('retries'));
    }
}
