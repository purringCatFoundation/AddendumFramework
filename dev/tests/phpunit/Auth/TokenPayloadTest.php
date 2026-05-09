<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PHPUnit\Framework\TestCase;

final class TokenPayloadTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: 1234567890,
            jti: 'jti-123',
            iat: 1234567800,
            tokenType: TokenType::USER,
            fingerprintHash: 'fingerprint-hash-789'
        );

        $this->assertSame('user-uuid-123', $payload->sub);
        $this->assertSame(1234567890, $payload->exp);
        $this->assertSame('jti-123', $payload->jti);
        $this->assertSame(1234567800, $payload->iat);
        $this->assertSame(TokenType::USER, $payload->tokenType);
        $this->assertSame('fingerprint-hash-789', $payload->fingerprintHash);
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: 1234567890,
            jti: 'jti-123',
            iat: 1234567800
        );

        $this->assertSame('user-uuid-123', $payload->sub);
        $this->assertNull($payload->tokenType);
        $this->assertNull($payload->fingerprintHash);
    }

    public function testJsonSerializeWithAllFields(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: 1234567890,
            jti: 'jti-123',
            iat: 1234567800,
            tokenType: TokenType::USER,
            fingerprintHash: 'fingerprint-hash-789'
        );

        $json = $payload->jsonSerialize();

        $this->assertSame('user-uuid-123', $json['sub']);
        $this->assertSame(1234567890, $json['exp']);
        $this->assertSame('jti-123', $json['jti']);
        $this->assertSame(1234567800, $json['iat']);
        $this->assertSame('user', $json['tokenType']);
        $this->assertSame('fingerprint-hash-789', $json['fingerprintHash']);
        $this->assertSame('user', $json['type']);
    }

    public function testJsonSerializeWithMinimalFields(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: 1234567890,
            jti: 'jti-123',
            iat: 1234567800
        );

        $json = $payload->jsonSerialize();

        $this->assertSame('user-uuid-123', $json['sub']);
        $this->assertSame(1234567890, $json['exp']);
        $this->assertArrayNotHasKey('tokenType', $json);
        $this->assertArrayNotHasKey('fingerprintHash', $json);
    }

    public function testFromArrayWithAllFields(): void
    {
        $data = [
            'sub' => 'user-uuid-123',
            'exp' => 1234567890,
            'jti' => 'jti-123',
            'iat' => 1234567800,
            'tokenType' => 'admin',
            'fingerprintHash' => 'fingerprint-hash-789'
        ];

        $payload = TokenPayload::fromArray($data);

        $this->assertSame('user-uuid-123', $payload->sub);
        $this->assertSame(1234567890, $payload->exp);
        $this->assertSame('jti-123', $payload->jti);
        $this->assertSame(1234567800, $payload->iat);
        $this->assertSame(TokenType::ADMIN, $payload->tokenType);
        $this->assertSame('fingerprint-hash-789', $payload->fingerprintHash);
    }

    public function testHasElevatedPrivilegesWithAdminToken(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800, TokenType::ADMIN);

        $this->assertTrue($payload->hasElevatedPrivileges());
    }

    public function testHasElevatedPrivilegesWithApplicationToken(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800, TokenType::APPLICATION);

        $this->assertTrue($payload->hasElevatedPrivileges());
    }

    public function testHasElevatedPrivilegesWithUserToken(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800, TokenType::USER);

        $this->assertFalse($payload->hasElevatedPrivileges());
    }

    public function testHasElevatedPrivilegesWithApplicationDefinedToken(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800, 'workspace_member');

        $this->assertFalse($payload->hasElevatedPrivileges());
    }

    public function testHasElevatedPrivilegesWithNullTokenType(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800);

        $this->assertFalse($payload->hasElevatedPrivileges());
    }

    public function testRequiresOwnershipValidationWithUserToken(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800, TokenType::USER);

        $this->assertTrue($payload->requiresOwnershipValidation());
    }

    public function testRequiresOwnershipValidationWithAdminToken(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800, TokenType::ADMIN);

        $this->assertFalse($payload->requiresOwnershipValidation());
    }

    public function testRequiresOwnershipValidationWithApplicationDefinedToken(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800, 'workspace_member');

        $this->assertTrue($payload->requiresOwnershipValidation());
    }

    public function testRequiresOwnershipValidationWithNullTokenType(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800);

        $this->assertTrue($payload->requiresOwnershipValidation());
    }

    public function testGetTokenTypeReturnsActualType(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800, 'workspace_member');

        $this->assertSame('workspace_member', $payload->getTokenType());
    }

    public function testGetTokenTypeDefaultsToUserWhenNull(): void
    {
        $payload = new TokenPayload('user-uuid-123', 1234567890, 'jti-123', 1234567800);

        $this->assertSame(TokenType::USER, $payload->getTokenType());
    }
}
