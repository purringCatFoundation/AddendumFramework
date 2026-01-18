<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Validation\Rules;

use CitiesRpg\ApiBackend\Validation\AbstractRequestValidator;

class In extends AbstractRequestValidator
{
    public function __construct(private readonly array $allowedValues)
    {
    }

    /**
     * Validate that value is in allowed list
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!in_array($value, $this->allowedValues, true)) {
            $allowedString = implode(', ', $this->allowedValues);
            return "Field must be one of: {$allowedString}";
        }

        return null;
    }
}