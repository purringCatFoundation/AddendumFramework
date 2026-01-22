<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use PCF\Addendum\Validation\AbstractRequestValidator;

class Email extends AbstractRequestValidator
{
    /**
     * Validate email format
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Field must be a valid email address";
        }

        return null;
    }
}