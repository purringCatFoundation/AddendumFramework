<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

final readonly class AttributeReaderFactory
{
    public function create(object $target): AttributeReader
    {
        return new AttributeReader($target);
    }
}
