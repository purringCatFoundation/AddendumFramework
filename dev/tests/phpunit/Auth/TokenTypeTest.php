<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\TokenType;
use PHPUnit\Framework\TestCase;

final class TokenTypeTest extends TestCase
{
    public function testAllTokenTypesExist(): void
    {
        $this->assertSame('admin', TokenType::ADMIN->value);
        $this->assertSame('application', TokenType::APPLICATION->value);
        $this->assertSame('user', TokenType::USER->value);
        $this->assertSame('user_refresh', TokenType::USER_REFRESH->value);
        $this->assertSame('character', TokenType::CHARACTER->value);
        $this->assertSame('character_refresh', TokenType::CHARACTER_REFRESH->value);
    }

    public function testHasElevatedPrivilegesForAdminAndApplication(): void
    {
        $this->assertTrue(TokenType::ADMIN->hasElevatedPrivileges());
        $this->assertTrue(TokenType::APPLICATION->hasElevatedPrivileges());
    }

    public function testHasElevatedPrivilegesReturnsFalseForRegularTokens(): void
    {
        $this->assertFalse(TokenType::USER->hasElevatedPrivileges());
        $this->assertFalse(TokenType::CHARACTER->hasElevatedPrivileges());
    }

    public function testRequiresOwnershipValidationForUserAndCharacter(): void
    {
        $this->assertTrue(TokenType::USER->requiresOwnershipValidation());
        $this->assertTrue(TokenType::CHARACTER->requiresOwnershipValidation());
    }

    public function testRequiresOwnershipValidationReturnsFalseForElevatedTokens(): void
    {
        $this->assertFalse(TokenType::ADMIN->requiresOwnershipValidation());
        $this->assertFalse(TokenType::APPLICATION->requiresOwnershipValidation());
    }

    public function testGetDescriptionForAllTypes(): void
    {
        $this->assertSame('Administrator with full access', TokenType::ADMIN->getDescription());
        $this->assertSame('Application-level system access', TokenType::APPLICATION->getDescription());
        $this->assertSame('Standard user with ownership-based access', TokenType::USER->getDescription());
        $this->assertSame('Character-specific access', TokenType::CHARACTER->getDescription());
    }

    public function testFromStringValue(): void
    {
        $this->assertSame(TokenType::ADMIN, TokenType::from('admin'));
        $this->assertSame(TokenType::APPLICATION, TokenType::from('application'));
        $this->assertSame(TokenType::USER, TokenType::from('user'));
        $this->assertSame(TokenType::CHARACTER, TokenType::from('character'));
    }

    public function testTryFromStringValue(): void
    {
        $this->assertSame(TokenType::ADMIN, TokenType::tryFrom('admin'));
        $this->assertNull(TokenType::tryFrom('invalid'));
    }

    public function testFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TokenType::from('invalid');
    }

    public function testAllCasesMethod(): void
    {
        $cases = TokenType::cases();

        $this->assertCount(6, $cases);
        $this->assertContains(TokenType::ADMIN, $cases);
        $this->assertContains(TokenType::APPLICATION, $cases);
        $this->assertContains(TokenType::USER, $cases);
        $this->assertContains(TokenType::USER_REFRESH, $cases);
        $this->assertContains(TokenType::CHARACTER, $cases);
        $this->assertContains(TokenType::CHARACTER_REFRESH, $cases);
    }
}
