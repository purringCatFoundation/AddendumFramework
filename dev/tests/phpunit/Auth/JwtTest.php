<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use DateTime;
use InvalidArgumentException;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class JwtTest extends TestCase
{
    private const int NOW = 1_700_000_000;

    #[DataProvider('invalidSessionIds')]
    public function testSessionTokensRejectMissingOrInvalidSessionId(string $tokenType, mixed $sid): void
    {
        $claims = [
            'sub' => 'user-1', 'exp' => self::NOW + 3600, 'jti' => 'jti-1',
            'iat' => self::NOW, 'tokenType' => $tokenType,
        ];
        if ($sid !== null) {
            $claims['sid'] = $sid;
        }
        $token = $this->encodePayload($claims, ['alg' => 'RS256', 'appTokenType' => $tokenType]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JWT claim: sid');
        $this->decode($token);
    }

    public static function invalidSessionIds(): iterable
    {
        foreach ([TokenType::USER, TokenType::ADMIN, TokenType::USER_REFRESH] as $type) {
            foreach ([null, '', '   ', 123, str_repeat('a', 256)] as $index => $sid) {
                yield $type . '-' . $index => [$type, $sid];
            }
        }
    }

    public function testApplicationTokenDoesNotRequireSessionId(): void
    {
        $token = $this->encodePayload([
            'sub' => 'service', 'exp' => self::NOW + 3600, 'jti' => 'app-jti',
            'iat' => self::NOW, 'tokenType' => TokenType::APPLICATION,
        ], ['alg' => 'RS256', 'appTokenType' => TokenType::APPLICATION]);

        self::assertArrayNotHasKey('sid', $this->decode($token)->jsonSerialize());
    }

    public function testDecodePreservesSessionId(): void
    {
        $token = $this->encodePayload([
            'sub' => 'user-1', 'exp' => self::NOW + 3600, 'jti' => 'jti-1',
            'iat' => self::NOW, 'tokenType' => TokenType::USER, 'sid' => 'session-1',
        ], ['alg' => 'RS256', 'appTokenType' => TokenType::USER]);

        self::assertSame('session-1', $this->decode($token)->jsonSerialize()['sid'] ?? null);
    }

    public function testEncodeAndDecodeSuccessfully(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW + 3600,
            jti: 'jti-123',
            iat: self::NOW,
            tokenType: TokenType::USER,
            sid: 'session-123'
        );

        $token = $this->encode($payload);
        $decoded = $this->decode($token);

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
            exp: self::NOW + 3600,
            jti: 'jti-123',
            iat: self::NOW,
            tokenType: TokenType::USER,
            sid: 'session-123'
        );

        $token = $this->encode($payload);

        // JWT should have 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        // Each part should be base64url encoded
        foreach ($parts as $part) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $part);
        }
    }

    public function testEncodeStoresTokenTypeOnlyInPayloadAndAppTokenTypeInHeader(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW + 3600,
            jti: 'jti-123',
            iat: self::NOW,
            tokenType: TokenType::APPLICATION
        );

        $token = $this->encode($payload);
        [$header, $claims] = $this->decodeCompactToken($token);

        $this->assertSame('RS256', $header['alg']);
        $this->assertSame(TokenType::APPLICATION, $header['appTokenType']);
        $this->assertSame(TokenType::APPLICATION, $claims['tokenType']);
        $this->assertArrayNotHasKey('type', $claims);
    }

    #[DataProvider('legacyTypeClaims')]
    public function testDecodeRejectsLegacyTypeClaim(mixed $legacyType): void
    {
        $token = $this->encodePayload([
            'sub' => 'user-uuid-123',
            'exp' => self::NOW + 3600,
            'jti' => 'jti-123',
            'iat' => self::NOW,
            'tokenType' => TokenType::USER,
            'sid' => 'session-123',
            'type' => $legacyType,
        ], ['alg' => 'RS256', 'appTokenType' => TokenType::USER]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JWT claim: type');

        $this->decode($token);
    }

    public static function legacyTypeClaims(): iterable
    {
        yield 'matching legacy type' => [TokenType::USER];
        yield 'conflicting legacy type' => [TokenType::ADMIN];
        yield 'null legacy type' => [null];
    }

    public function testDecodeRejectsMissingAppTokenTypeHeader(): void
    {
        $token = $this->encodePayload([
            'sub' => 'user-uuid-123',
            'exp' => self::NOW + 3600,
            'jti' => 'jti-123',
            'iat' => self::NOW,
            'tokenType' => TokenType::USER,
        ], ['alg' => 'RS256']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required JWT header: appTokenType');

        $this->decode($token);
    }

    public function testEncodeWithEncryptedPrivateKey(): void
    {
        $keyPair = JwtKeyPair::create('test-passphrase');
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW + 3600,
            jti: 'jti-123',
            iat: self::NOW,
            tokenType: TokenType::USER,
            sid: 'session-123'
        );

        $token = Jwt::encode($payload, $keyPair->privateKeyPath, $keyPair->privateKeyPassphrase);
        $decoded = Jwt::decode($token, $keyPair->publicKeyPath, $this->dateTime());

        $this->assertSame($payload->sub, $decoded->sub);
    }

    public function testDecodeWithInvalidSignatureThrowsException(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW + 3600,
            jti: 'jti-123',
            iat: self::NOW,
            tokenType: TokenType::USER,
            sid: 'session-123'
        );

        $token = $this->encode($payload);
        $wrongKeyPair = JwtKeyPair::create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid signature');

        Jwt::decode($token, $wrongKeyPair->publicKeyPath, $this->dateTime());
    }

    public function testDecodeExpiredTokenThrowsException(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW - 3600,
            jti: 'jti-123',
            iat: self::NOW - 7200,
            tokenType: TokenType::USER,
            sid: 'session-123'
        );

        $token = $this->encode($payload);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token expired');

        $this->decode($token);
    }

    public function testDecodeWithMalformedTokenThrowsException(): void
    {
        $this->expectException(\Exception::class);

        $this->decode('invalid.token');
    }

    public function testEncodeWithAllPayloadFields(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW + 3600,
            jti: 'jti-123',
            iat: self::NOW,
            tokenType: 'workspace_member',
            fingerprintHash: 'fingerprint-hash-789'
        );

        $token = $this->encode($payload);
        $decoded = $this->decode($token);

        $this->assertSame($payload->fingerprintHash, $decoded->fingerprintHash);
        $this->assertSame('workspace_member', $decoded->tokenType);
    }

    public function testTokenExpirationEdgeCase(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW,
            jti: 'jti-123',
            iat: self::NOW - 60,
            tokenType: TokenType::USER,
            sid: 'session-123'
        );

        $token = $this->encode($payload);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token expired');

        $this->decode($token);
    }

    public function testTokenNotYetExpired(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW + 1,
            jti: 'jti-123',
            iat: self::NOW,
            tokenType: TokenType::USER,
            sid: 'session-123'
        );

        $token = $this->encode($payload);
        $decoded = $this->decode($token);

        $this->assertSame($payload->sub, $decoded->sub);
    }

    public function testTokenIssuedInFutureThrowsException(): void
    {
        $payload = new TokenPayload(
            sub: 'user-uuid-123',
            exp: self::NOW + 3600,
            jti: 'jti-123',
            iat: self::NOW + 61,
            tokenType: TokenType::USER,
            sid: 'session-123'
        );

        $token = $this->encode($payload);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token issued in the future');

        $this->decode($token);
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
                exp: self::NOW + 3600,
                jti: 'jti-' . $tokenType,
                iat: self::NOW,
                tokenType: $tokenType,
                sid: 'session-123'
            );

            $token = $this->encode($payload);
            $decoded = $this->decode($token);

            $this->assertSame($tokenType, $decoded->tokenType);
        }
    }

    private function encode(TokenPayload $payload): string
    {
        $keyPair = JwtKeyPair::shared();

        return Jwt::encode($payload, $keyPair->privateKeyPath, $keyPair->privateKeyPassphrase);
    }

    private function decode(string $token): TokenPayload
    {
        return Jwt::decode($token, JwtKeyPair::shared()->publicKeyPath, $this->dateTime());
    }

    private function dateTime(): DateTime
    {
        return new DateTime('@' . self::NOW);
    }

    private function encodePayload(array $payload, array $protectedHeader): string
    {
        $keyPair = JwtKeyPair::shared();
        $jwk = JWKFactory::createFromKeyFile($keyPair->privateKeyPath, $keyPair->privateKeyPassphrase);
        $builder = new JWSBuilder(new AlgorithmManager([new RS256()]));
        $jws = $builder
            ->create()
            ->withPayload(json_encode($payload, JSON_THROW_ON_ERROR))
            ->addSignature($jwk, $protectedHeader)
            ->build();

        return new CompactSerializer()->serialize($jws, 0);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function decodeCompactToken(string $token): array
    {
        [$header, $payload] = explode('.', $token, 3);

        return [
            json_decode($this->base64UrlDecode($header), true, flags: JSON_THROW_ON_ERROR),
            json_decode($this->base64UrlDecode($payload), true, flags: JSON_THROW_ON_ERROR),
        ];
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4));
    }
}
