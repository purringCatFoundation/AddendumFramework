<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use PCF\Addendum\Validation\AbstractRequestValidator;

class MinLength extends AbstractRequestValidator
{
    public function __construct(private readonly int $minLength)
    {
    }

    /**
     * Validate minimum string length
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (strlen((string)$value) < $this->minLength) {
            return "Field must be at least {$this->minLength} characters long";
        }

        return null;
    }
}