<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\TokenType;
use PHPUnit\Framework\TestCase;

final class TokenTypeTest extends TestCase
{
    public function testFrameworkTokenTypeConstants(): void
    {
        $this->assertSame('admin', TokenType::ADMIN);
        $this->assertSame('application', TokenType::APPLICATION);
        $this->assertSame('user', TokenType::USER);
        $this->assertSame('user_refresh', TokenType::USER_REFRESH);
    }

    public function testHasElevatedPrivilegesForAdminAndApplication(): void
    {
        $this->assertTrue(TokenType::hasElevatedPrivileges(TokenType::ADMIN));
        $this->assertTrue(TokenType::hasElevatedPrivileges(TokenType::APPLICATION));
    }

    public function testHasElevatedPrivilegesReturnsFalseForRegularAndApplicationDefinedTokens(): void
    {
        $this->assertFalse(TokenType::hasElevatedPrivileges(TokenType::USER));
        $this->assertFalse(TokenType::hasElevatedPrivileges('workspace_member'));
    }

    public function testRequiresOwnershipValidationForNonElevatedTokens(): void
    {
        $this->assertTrue(TokenType::requiresOwnershipValidation(TokenType::USER));
        $this->assertTrue(TokenType::requiresOwnershipValidation('workspace_member'));
    }

    public function testRequiresOwnershipValidationReturnsFalseForElevatedTokens(): void
    {
        $this->assertFalse(TokenType::requiresOwnershipValidation(TokenType::ADMIN));
        $this->assertFalse(TokenType::requiresOwnershipValidation(TokenType::APPLICATION));
    }

    public function testDescriptionForFrameworkAndApplicationDefinedTypes(): void
    {
        $this->assertSame('Administrator with full access', TokenType::description(TokenType::ADMIN));
        $this->assertSame('Application-level system access', TokenType::description(TokenType::APPLICATION));
        $this->assertSame('Standard user with ownership-based access', TokenType::description(TokenType::USER));
        $this->assertSame('Application-defined token type "workspace_member"', TokenType::description('workspace_member'));
    }

    public function testBuiltInTypes(): void
    {
        $this->assertSame(
            [TokenType::ADMIN, TokenType::APPLICATION, TokenType::USER, TokenType::USER_REFRESH],
            TokenType::builtInTypes()->toArray()
        );
    }
}
