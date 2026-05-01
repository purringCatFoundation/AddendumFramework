<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

final class AttributeValue implements AttributeValueInterface
{
    private int $position = 0;

    /**
     * @param list<mixed> $values
     */
    public function __construct(
        private readonly string $attributeType,
        private readonly array $values
    ) {
    }

    public function getAttributeType(): string
    {
        return $this->attributeType;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getFirst(mixed $default = null): mixed
    {
        return array_key_exists(0, $this->values) ? $this->values[0] : $default;
    }

    public function current(): mixed
    {
        return $this->values[$this->position] ?? null;
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
        return array_key_exists($this->position, $this->values);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
