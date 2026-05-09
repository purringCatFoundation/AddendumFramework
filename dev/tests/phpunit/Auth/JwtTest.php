<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

final class JwtTest extends TestCase
{
    private const TEST_SECRET = 'test-secret-key-for-jwt-signing-must-be-long';

    public function testEncodeAndDecodeSuccessfully(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: time() + 3600,
            jti: 'jti-123',
            iat: time(),
            tokenType: TokenType::USER
        );

        $token = Jwt::encode($payload, self::TEST_SECRET);
        $decoded = Jwt::decode($token, self::TEST_SECRET);

        $this->assertSame($payload->sub, $decoded->sub);
        $this->assertSame($payload->exp, $decoded->exp);
        $this->assertSame($payload->jti, $decoded->jti);
        $this->assertSame($payload->iat, $decoded->iat);
        $this->assertSame($payload->tokenType, $decoded->tokenType);
    }

    public function testEncodeReturnsValidJwtFormat(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: time() + 3600,
            jti: 'jti-123',
            iat: time(),
            tokenType: TokenType::USER
        );

        $token = Jwt::encode($payload, self::TEST_SECRET);

        // JWT should have 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        // Each part should be base64url encoded
        foreach ($parts as $part) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $part);
        }
    }

    public function testDecodeWithInvalidSignatureThrowsException(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: time() + 3600,
            jti: 'jti-123',
            iat: time(),
            tokenType: TokenType::USER
        );

        $token = Jwt::encode($payload, self::TEST_SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid signature');

        Jwt::decode($token, 'wrong-secret-key-for-jwt-signing-must-be-long');
    }

    public function testDecodeExpiredTokenThrowsException(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: time() - 3600, // Expired 1 hour ago
            jti: 'jti-123',
            iat: time() - 7200,
            tokenType: TokenType::USER
        );

        $token = Jwt::encode($payload, self::TEST_SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token expired');

        Jwt::decode($token, self::TEST_SECRET);
    }

    public function testDecodeWithMalformedTokenThrowsException(): void
    {
        $this->expectException(\Exception::class);

        Jwt::decode('invalid.token', self::TEST_SECRET);
    }

    public function testEncodeWithAllPayloadFields(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: time() + 3600,
            jti: 'jti-123',
            iat: time(),
            tokenType: 'workspace_member',
            fingerprintHash: 'fingerprint-hash-789'
        );

        $token = Jwt::encode($payload, self::TEST_SECRET);
        $decoded = Jwt::decode($token, self::TEST_SECRET);

        $this->assertSame($payload->fingerprintHash, $decoded->fingerprintHash);
        $this->assertSame('workspace_member', $decoded->tokenType);
    }

    public function testTokenExpirationEdgeCase(): void
    {
        // Token that expires exactly now should be invalid
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: time(),
            jti: 'jti-123',
            iat: time() - 60,
            tokenType: TokenType::USER
        );

        $token = Jwt::encode($payload, self::TEST_SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token expired');

        Jwt::decode($token, self::TEST_SECRET);
    }

    public function testTokenNotYetExpired(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: time() + 1, // Expires in 1 second
            jti: 'jti-123',
            iat: time(),
            tokenType: TokenType::USER
        );

        $token = Jwt::encode($payload, self::TEST_SECRET);
        $decoded = Jwt::decode($token, self::TEST_SECRET);

        $this->assertSame($payload->sub, $decoded->sub);
    }

    public function testEncodeWithDifferentTokenTypes(): void
    {
        $tokenTypes = [
            TokenType::ADMIN,
            TokenType::APPLICATION,
            TokenType::USER,
            'workspace_member',
        ];

        foreach ($tokenTypes as $tokenType) {
            $payload = new TokenPayload(
                sub: 'user-uuid-123',
                exp: time() + 3600,
                jti: 'jti-' . $tokenType,
                iat: time(),
                tokenType: $tokenType
            );

            $token = Jwt::encode($payload, self::TEST_SECRET);
            $decoded = Jwt::decode($token, self::TEST_SECRET);

            $this->assertSame($tokenType, $decoded->tokenType);
        }
    }
}
