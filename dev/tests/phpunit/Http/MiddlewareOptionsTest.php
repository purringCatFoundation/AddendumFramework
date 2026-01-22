<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Http;

use PCF\Addendum\Http\MiddlewareOptions;
use PHPUnit\Framework\TestCase;

class MiddlewareOptionsTest extends TestCase
{
    public function testConstructor(): void
    {
        $options = new MiddlewareOptions('MyAction', ['key' => 'value']);
        
        $this->assertEquals('MyAction', $options->actionClass);
        $this->assertEquals(['key' => 'value'], $options->additionalData);
    }

    public function testFromArray(): void
    {
        $array = ['actionClass' => 'TestAction', 'ttl' => 60, 'key' => 'cache'];
        $options = MiddlewareOptions::fromArray($array);
        
        $this->assertEquals('TestAction', $options->actionClass);
        $this->assertEquals(['ttl' => 60, 'key' => 'cache'], $options->additionalData);
    }

    public function testFromArrayWithoutActionClass(): void
    {
        $array = ['ttl' => 60, 'key' => 'cache'];
        $options = MiddlewareOptions::fromArray($array);
        
        $this->assertEquals('', $options->actionClass);
        $this->assertEquals(['ttl' => 60, 'key' => 'cache'], $options->additionalData);
    }

    public function testToArray(): void
    {
        $options = new MiddlewareOptions('TestAction', ['ttl' => 60, 'key' => 'cache']);
        $array = $options->toArray();
        
        $this->assertEquals(['actionClass' => 'TestAction', 'ttl' => 60, 'key' => 'cache'], $array);
    }

    public function testWithActionClass(): void
    {
        $options = new MiddlewareOptions('OldAction', ['key' => 'value']);
        $newOptions = $options->withActionClass('NewAction');
        
        $this->assertEquals('OldAction', $options->actionClass);
        $this->assertEquals('NewAction', $newOptions->actionClass);
        $this->assertEquals($options->additionalData, $newOptions->additionalData);
    }

    public function testWithAdditionalData(): void
    {
        $options = new MiddlewareOptions('TestAction', ['key1' => 'value1']);
        $newOptions = $options->withAdditionalData(['key2' => 'value2']);
        
        $this->assertEquals(['key1' => 'value1'], $options->additionalData);
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $newOptions->additionalData);
    }

    public function testGet(): void
    {
        $options = new MiddlewareOptions('TestAction', ['key' => 'value', 'number' => 42]);
        
        $this->assertEquals('value', $options->get('key'));
        $this->assertEquals(42, $options->get('number'));
        $this->assertNull($options->get('nonexistent'));
        $this->assertEquals('default', $options->get('nonexistent', 'default'));
    }
}