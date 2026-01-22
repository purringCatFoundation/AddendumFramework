<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

/**
 * Token type enum for access control system
 * Defines different levels of access:
 * - ADMIN: Full administrative access, bypasses all ownership checks
 * - APPLICATION: System-level access for inter-service communication
 * - USER: Standard user access, subject to ownership validation
 * - CHARACTER: Character-specific access, subject to ownership validation
 */
enum TokenType: string
{
    case ADMIN = 'admin';
    case APPLICATION = 'application';
    case USER = 'user';
    case USER_REFRESH = 'user_refresh';
    case CHARACTER = 'character';
    case CHARACTER_REFRESH = 'character_refresh';

    /**
     * Check if this token type has elevated privileges (bypasses ownership checks)
     */
    public function hasElevatedPrivileges(): bool
    {
        return match ($this) {
            self::ADMIN, self::APPLICATION => true,
            self::USER, self::CHARACTER => false,
        };
    }

    /**
     * Check if this token type requires ownership validation
     */
    public function requiresOwnershipValidation(): bool
    {
        return !$this->hasElevatedPrivileges();
    }

    /**
     * Get human-readable description of the token type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator with full access',
            self::APPLICATION => 'Application-level system access',
            self::USER => 'Standard user with ownership-based access',
            self::CHARACTER => 'Character-specific access',
        };
    }
}
