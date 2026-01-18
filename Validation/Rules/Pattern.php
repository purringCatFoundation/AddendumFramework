<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Validation\Rules;

use CitiesRpg\ApiBackend\Validation\AbstractRequestValidator;

class Pattern extends AbstractRequestValidator
{
    public function __construct(
        private readonly string  $pattern,
        private readonly ?string $errorMessage = null
    ) {
    }

    /**
     * Validate value against regex pattern
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!preg_match($this->pattern, (string)$value)) {
            return $this->errorMessage ?? "Field does not match the required pattern";
        }

        return null;
    }
}
