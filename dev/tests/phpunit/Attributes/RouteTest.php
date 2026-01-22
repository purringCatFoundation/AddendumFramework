<?php
declare(strict_types=1);

namespace CitiesRpg\Tests\Attributes;

use PCF\Addendum\Attribute\Route;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    public function testStoresPathAndMethod(): void
    {
        $attribute = new Route('/path/:id', 'GET', ['id' => '\\d+']);
        $this->assertSame('/path/:id', $attribute->path);
        $this->assertSame('GET', $attribute->method);
        $this->assertSame(['id' => '\\d+'], $attribute->requirements);
    }
}
