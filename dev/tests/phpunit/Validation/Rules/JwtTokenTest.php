<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation\Rules;

use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Validation\Rules\JwtToken;
use PCF\Addendum\Validation\Rules\JwtTokenValidator;
use PHPUnit\Framework\TestCase;

final class JwtTokenTest extends TestCase
{
    private const string SECRET = '0123456789abcdef0123456789abcdef';

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
        $repository->expects(self::once())->method('isTokenValid')->with('user-1', self::isType('int'))->willReturn(true);
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
        return new JwtTokenValidator(
            new JwtConfig(self::SECRET, 7200, 1209600),
            $repository ?? $this->createMock(TokenValidationRepository::class),
            $requiredTokenType
        );
    }

    private function jwt(string $tokenType): string
    {
        return Jwt::encode(new TokenPayload(
            sub: 'user-1',
            exp: time() + 3600,
            jti: 'jti-1',
            iat: time(),
            tokenType: $tokenType,
            fingerprintHash: 'fingerprint-hash'
        ), self::SECRET);
    }
}
