<?php
declare(strict_types=1);

namespace CitiesRpg\Tests\Attributes;

use PCF\Addendum\Attribute\Middleware;
use PHPUnit\Framework\TestCase;

final class MiddlewareTest extends TestCase
{
    public function testStoresClass(): void
    {
        $attribute = new Middleware('Foo', ['bar' => 'baz']);
        $this->assertSame('Foo', $attribute->middlewareClass);
        $this->assertSame(['bar' => 'baz'], $attribute->options);
    }
}
