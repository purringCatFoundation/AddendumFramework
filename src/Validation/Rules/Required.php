<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use PCF\Addendum\Validation\AbstractRequestValidator;

class Required extends AbstractRequestValidator
{
    /**
     * Validate that value is present and not null
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if (is_null($value)) {
            return "Field is required";
        }

        return null;
    }
}