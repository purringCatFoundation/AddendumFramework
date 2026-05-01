<?php
declare(strict_types=1);

namespace CitiesRpg\Tests\Attributes;

use PCF\Addendum\Attribute\AttributeValue;
use PCF\Addendum\Attribute\Name;
use PHPUnit\Framework\TestCase;

final class AttributeValueTest extends TestCase
{
    public function testStoresAttributeTypeAndValues(): void
    {
        $value = new AttributeValue(Name::class, ['first', 'second']);

        $this->assertSame(Name::class, $value->getAttributeType());
        $this->assertSame(['first', 'second'], $value->getValues());
    }

    public function testReturnsFirstValue(): void
    {
        $value = new AttributeValue(Name::class, ['first', 'second']);

        $this->assertSame('first', $value->getFirst());
    }

    public function testReturnsFirstNullValue(): void
    {
        $value = new AttributeValue(Name::class, [null]);

        $this->assertNull($value->getFirst('fallback'));
    }

    public function testReturnsDefaultWhenValuesAreEmpty(): void
    {
        $value = new AttributeValue(Name::class, []);

        $this->assertSame('fallback', $value->getFirst('fallback'));
    }

    public function testIsIterable(): void
    {
        $value = new AttributeValue(Name::class, ['first', 'second']);

        $this->assertSame(['first', 'second'], iterator_to_array($value));
    }
}
