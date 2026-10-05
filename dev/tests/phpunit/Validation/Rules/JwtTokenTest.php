<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\ApplicationTokenValidator;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use PCF\Addendum\Validation\Rules\JwtToken;
use PCF\Addendum\Validation\Rules\JwtTokenValidator;
use PHPUnit\Framework\TestCase;

final class JwtTokenTest extends TestCase
{
    public function testConstraintStoresRequiredTokenType(): void
    {
        $constraint = new JwtToken(TokenType::USER_REFRESH);

        self::assertSame(TokenType::USER_REFRESH, $constraint->requiredTokenType());
    }

    public function testValidatorWithMissingToken(): void
    {
        $validator = $this->validator();

        $this->assertEquals('Token is required', $validator->validate(null));
        $this->assertEquals('Token is required', $validator->validate(''));
    }

    public function testValidatorWithEmptyBearerToken(): void
    {
        $validator = $this->validator();

        $this->assertEquals('Token is required', $validator->validate('   '));
    }

    public function testValidatorWithMalformedJwtToken(): void
    {
        $result = $this->validator()->validate('malformed.token');

        $this->assertStringStartsWith('Invalid token:', $result);
    }

    public function testValidatorAcceptsValidNonRevokedToken(): void
    {
        $repository = $this->createMock(TokenValidationRepository::class);
        $repository->expects(self::once())->method('isTokenValid')->with(TokenType::USER, 'user-1', 'jti-1', self::isInt(), 'session-1')->willReturn(true);
        $validator = $this->validator($repository);

        self::assertNull($validator->validate($this->jwt(TokenType::USER)));
    }

    public function testValidatorRejectsWrongTokenType(): void
    {
        $validator = $this->validator(requiredTokenType: TokenType::USER_REFRESH);

        self::assertSame(
            "Invalid token type, expected 'user_refresh'",
            $validator->validate($this->jwt(TokenType::USER))
        );
    }

    public function testValidatorAddsRequestAttributeValue(): void
    {
        self::assertSame(['jwt_token' => 'token'], $this->validator()->requestAttributes(' token ')->toArray());
    }

    public function testIsValidMethod(): void
    {
        $validator = $this->validator();

        $this->assertFalse($validator->isValid(null));
        $this->assertFalse($validator->isValid('malformed.token'));
    }

    private function validator(
        ?TokenValidationRepository $repository = null,
        string $requiredTokenType = TokenType::USER
    ): JwtTokenValidator {
        $keyPair = JwtKeyPair::shared();

        return new JwtTokenValidator(
            new JwtConfig($keyPair->privateKeyPath, $keyPair->publicKeyPath, $keyPair->privateKeyPassphrase, 7200, 1209600),
            $repository ?? $this->createMock(TokenValidationRepository::class),
            $this->createMock(ApplicationTokenValidator::class),
            $requiredTokenType
        );
    }

    private function jwt(string $tokenType): string
    {
        $keyPair = JwtKeyPair::shared();

        return Jwt::encode(new TokenPayload(
            sub: 'user-1',
            exp: time() + 3600,
            jti: 'jti-1',
            iat: time(),
            tokenType: $tokenType,
            fingerprintHash: 'fingerprint-hash',
            sid: 'session-1'
        ), $keyPair->privateKeyPath, $keyPair->privateKeyPassphrase);
    }
}
