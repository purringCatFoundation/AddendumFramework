<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Ds\Vector;

final class AttributeValue implements AttributeValueInterface
{
    private int $position = 0;

    /** @var Vector<mixed> */
    private readonly Vector $values;

    /**
     * @param iterable<mixed> $values
     */
    public function __construct(
        private readonly string $attributeType,
        iterable $values
    ) {
        $this->values = $values instanceof Vector ? $values->copy() : new Vector($values);
    }

    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    /** @return Vector<mixed> */
    public function getValues(): Vector
    {
        return $this->values->copy();
    }

    public function getFirst(mixed $default = null): mixed
    {
        return $this->values->isEmpty() ? $default : $this->values->first();
    }

    public function current(): mixed
    {
        return $this->values->get($this->position, null);
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->position < $this->values->count();
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
