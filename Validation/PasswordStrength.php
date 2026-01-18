<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Validation;

use CitiesRpg\ApiBackend\Validation\AbstractRequestValidator;

/**
 * Password strength validation rule
 *
 * Validates password complexity and strength:
 * - Minimum 12 characters
 * - At least 3 of 4: uppercase, lowercase, numbers, special characters
 * - Not in common password list
 *
 * Example usage:
 * #[ValidateRequest('password', new PasswordStrength())]
 */
class PasswordStrength extends AbstractRequestValidator
{
    private const MIN_LENGTH = 12;
    private const COMMON_PASSWORDS = [
        'password', '12345678', 'qwerty123', 'password123',
        'admin123', 'letmein', 'welcome123', '1234567890',
        'password1', 'abc123456', 'passw0rd', 'password1234',
        'qwertyuiop', '1q2w3e4r5t', 'adminadmin', 'welcome1',
        'trustno1', '123456789', 'qwerty1234'
    ];

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return 'Password is required';
        }

        if (!is_string($value)) {
            return 'Password must be a string';
        }

        if (strlen($value) < self::MIN_LENGTH) {
            return 'Password must be at least ' . self::MIN_LENGTH . ' characters';
        }

        // Check complexity: at least 3 of 4 (upper, lower, number, special)
        $checks = [
            preg_match('/[A-Z]/', $value),
            preg_match('/[a-z]/', $value),
            preg_match('/[0-9]/', $value),
            preg_match('/[^A-Za-z0-9]/', $value)
        ];

        if (array_sum($checks) < 3) {
            return 'Password must contain at least 3 of: uppercase, lowercase, numbers, special characters';
        }

        if (in_array(strtolower($value), self::COMMON_PASSWORDS, true)) {
            return 'Password is too common. Please choose a stronger password';
        }

        return null;
    }
}
