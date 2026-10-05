<?php

declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Ds\Vector;
use Iterator;

/** @extends Iterator<int, mixed> */
interface AttributeValueInterface extends Iterator
{
    public function getAttributeType(): string;

    /**
     * @return Vector<mixed>
     */
    public function getValues(): Vector;

    public function getFirst(mixed $default = null): mixed;
}
