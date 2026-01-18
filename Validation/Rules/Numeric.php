<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Validation\Rules;

use CitiesRpg\ApiBackend\Validation\AbstractRequestValidator;

class Numeric extends AbstractRequestValidator
{
    /**
     * Validate that value is numeric
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if ($value === null || empty($value)) {
            return null;
        }

        if (!is_numeric($value)) {
            return "Field must be a number";
        }

        return null;
    }
}
