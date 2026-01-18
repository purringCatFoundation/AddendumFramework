<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Validation\Rules;

use CitiesRpg\ApiBackend\Validation\AbstractRequestValidator;

/**
 * UUID v4 validation rule
 *
 * Validates that a value is a valid UUID v4 format.
 *
 * Example usage:
 * #[ValidateRequest('characterUuid', new Uuid())]
 */
class Uuid extends AbstractRequestValidator
{
    private const UUID_V4_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'UUID must be a string';
        }

        if (!preg_match(self::UUID_V4_PATTERN, $value)) {
            return 'Invalid UUID format (expected UUID v4)';
        }

        return null;
    }
}
