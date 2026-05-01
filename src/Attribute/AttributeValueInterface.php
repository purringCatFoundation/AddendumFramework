<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Iterator;

interface AttributeValueInterface extends Iterator
{
    public function getAttributeType(): string;

    /**
     * @return list<mixed>
     */
    public function getValues(): array;

    public function getFirst(mixed $default = null): mixed;
}
