<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Http\MiddlewareOptions;
use PHPUnit\Framework\TestCase;

class MiddlewareOptionsTest extends TestCase
{
    public function testConstructor(): void
    {
        $options = new MiddlewareOptions(['key' => 'value']);
        
        $this->assertEquals(['key' => 'value'], $options->additionalData->toArray());
    }

    public function testFromArray(): void
    {
        $array = ['actionClass' => 'TestAction', 'ttl' => 60, 'key' => 'cache'];
        $options = MiddlewareOptions::fromArray($array);
        
        $this->assertEquals(['ttl' => 60, 'key' => 'cache'], $options->additionalData->toArray());
    }

    public function testFromArrayWithoutActionClass(): void
    {
        $array = ['ttl' => 60, 'key' => 'cache'];
        $options = MiddlewareOptions::fromArray($array);
        
        $this->assertEquals(['ttl' => 60, 'key' => 'cache'], $options->additionalData->toArray());
    }

    public function testToArray(): void
    {
        $options = new MiddlewareOptions(['ttl' => 60, 'key' => 'cache']);
        $array = $options->toArray();
        
        $this->assertEquals(['ttl' => 60, 'key' => 'cache'], $array);
    }

    public function testWithAdditionalData(): void
    {
        $options = new MiddlewareOptions(['key1' => 'value1']);
        $newOptions = $options->withAdditionalData(['key2' => 'value2']);
        
        $this->assertEquals(['key1' => 'value1'], $options->additionalData->toArray());
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $newOptions->additionalData->toArray());
    }

    public function testGet(): void
    {
        $options = new MiddlewareOptions(['key' => 'value', 'number' => 42]);
        
        $this->assertEquals('value', $options->get('key'));
        $this->assertEquals(42, $options->get('number'));
        $this->assertNull($options->get('nonexistent'));
        $this->assertEquals('default', $options->get('nonexistent', 'default'));
    }
}
