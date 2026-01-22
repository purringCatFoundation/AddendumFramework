<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use PCF\Addendum\Validation\AbstractRequestValidator;

class MaxLength extends AbstractRequestValidator
{
    public function __construct(private readonly int $maxLength)
    {
    }

    /**
     * Validate maximum string length
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (strlen((string)$value) > $this->maxLength) {
            return "Field must not exceed {$this->maxLength} characters";
        }

        return null;
    }
}