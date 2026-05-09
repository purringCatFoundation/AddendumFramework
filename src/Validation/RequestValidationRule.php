<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use JsonSerializable;

final readonly class RequestValidationRule implements JsonSerializable
{
    public function __construct(
        public string $fieldName,
        public RequestFieldSource $source,
        public RequestValidationConstraintCollection $constraints
    ) {
    }

    public static function fromConstraints(
        string $fieldName,
        RequestFieldSource $source,
        RequestValidationConstraintInterface ...$constraints
    ): self {
        return new self($fieldName, $source, RequestValidationConstraintCollection::of(...$constraints));
    }

    public function jsonSerialize(): array
    {
        return [
            'fieldName' => $this->fieldName,
            'source' => $this->source->value,
            'constraints' => $this->constraints,
        ];
    }
}
