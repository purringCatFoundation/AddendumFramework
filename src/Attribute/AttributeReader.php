<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use InvalidArgumentException;
use ReflectionClass;

final readonly class AttributeReader
{
    protected ReflectionClass $reflection;
    public function __construct(object $target)
    {
        $this->reflection = new ReflectionClass($target);
    }

    public function getAttributeValues(string $attributeType, string $property): AttributeValueInterface
    {
        $values = [];

        foreach ($this->reflection->getAttributes($attributeType) as $attribute) {
            $instance = $attribute->newInstance();

            if (!property_exists($instance, $property)) {
                throw new InvalidArgumentException(sprintf(
                    'Attribute %s does not expose property "%s"',
                    $attributeType,
                    $property
                ));
            }

            $values[] = $instance->$property;
        }

        return new AttributeValue($attributeType, $values);
    }
}
