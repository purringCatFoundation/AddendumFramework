<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use Ds\Vector;

/**
 * Framework token type names and policy helpers.
 */
final class TokenType
{
    public const string ADMIN = 'admin';
    public const string APPLICATION = 'application';
    public const string USER = 'user';
    public const string USER_REFRESH = 'user_refresh';

    private const array ELEVATED_TYPES = [
        self::ADMIN,
        self::APPLICATION,
    ];

    private const array DESCRIPTIONS = [
        self::ADMIN => 'Administrator with full access',
        self::APPLICATION => 'Application-level system access',
        self::USER => 'Standard user with ownership-based access',
        self::USER_REFRESH => 'Refresh token for standard user access',
    ];

    private function __construct()
    {
    }

    public static function hasElevatedPrivileges(string $tokenType): bool
    {
        return in_array($tokenType, self::ELEVATED_TYPES, true);
    }

    public static function requiresOwnershipValidation(string $tokenType): bool
    {
        return !self::hasElevatedPrivileges($tokenType);
    }

    public static function description(string $tokenType): string
    {
        return self::DESCRIPTIONS[$tokenType] ?? sprintf('Application-defined token type "%s"', $tokenType);
    }

    /** @return Vector<string> */
    public static function builtInTypes(): Vector
    {
        return new Vector([
            self::ADMIN,
            self::APPLICATION,
            self::USER,
            self::USER_REFRESH,
        ]);
    }
}
