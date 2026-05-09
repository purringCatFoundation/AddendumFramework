<?php
declare(strict_types=1);

namespace CitiesRpg\Tests\Attributes;

use InvalidArgumentException;
use PCF\Addendum\Attribute\Actions;
use PCF\Addendum\Attribute\AttributeReader;
use PCF\Addendum\Attribute\AttributeValueInterface;
use PCF\Addendum\Attribute\Name;
use PHPUnit\Framework\TestCase;

final class AttributeReaderTest extends TestCase
{
    public function testReadsSingleAttributeValue(): void
    {
        $reader = new AttributeReader(new AttributeReaderFixture());
        $value = $reader->getAttributeValues(Name::class, 'value');

        $this->assertInstanceOf(AttributeValueInterface::class, $value);
        $this->assertSame(Name::class, $value->getAttributeType());
        $this->assertSame(['Demo'], $value->getValues()->toArray());
        $this->assertSame('Demo', $value->getFirst());
    }

    public function testReadsRepeatableAttributeValues(): void
    {
        $reader = new AttributeReader(new AttributeReaderFixture());
        $value = $reader->getAttributeValues(Actions::class, 'path');

        $this->assertSame(['/one', '/two'], $value->getValues()->toArray());
    }

    public function testReturnsEmptyValueForMissingAttribute(): void
    {
        $reader = new AttributeReader(new AttributeReaderWithoutAttributesFixture());
        $value = $reader->getAttributeValues(Actions::class, 'path');

        $this->assertSame(Actions::class, $value->getAttributeType());
        $this->assertSame([], $value->getValues()->toArray());
        $this->assertSame('fallback', $value->getFirst('fallback'));
    }

    public function testThrowsWhenAttributePropertyIsMissing(): void
    {
        $reader = new AttributeReader(new AttributeReaderFixture());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute ' . Name::class . ' does not expose property "missing"');

        $reader->getAttributeValues(Name::class, 'missing');
    }
}

#[Name('Demo')]
#[Actions('/one')]
#[Actions('/two')]
final class AttributeReaderFixture
{
}

final class AttributeReaderWithoutAttributesFixture
{
}
