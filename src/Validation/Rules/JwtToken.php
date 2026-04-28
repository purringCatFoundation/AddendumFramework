<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Validation\AbstractRequestValidator;
use InvalidArgumentException;

class JwtToken extends AbstractRequestValidator
{
    /**
     * Initialize JWT token validator with optional token type requirement
     */
    public function __construct(
        private readonly TokenType $requiredTokenType = TokenType::USER
    ) {
    }

    /**
     * Validate JWT token
     *
     * @param mixed $value Token string extracted from Authorization header
     * @return string|null Validation error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if (empty($value)) {
            return 'Token is required';
        }

        $token = trim((string) $value);
        if (empty($token)) {
            return 'Token is required';
        }

        try {
            $jwtConfig = new JwtConfigFactory()->create();

            $payload = Jwt::decode($token, $jwtConfig->secret);

            if ($this->requiredTokenType !== null && $payload->tokenType !== $this->requiredTokenType) {
                return "Invalid token type, expected '{$this->requiredTokenType}'";
            }

            $tokenValidationRepository = new TokenValidationRepositoryFactory()->create();
            if (!$tokenValidationRepository->isTokenValid($payload->sub, $payload->iat)) {
                return 'Token has been revoked';
            }

        } catch (InvalidArgumentException $e) {
            return 'Invalid token: ' . $e->getMessage();
        } catch (\RuntimeException $e) {
            return 'Configuration error: ' . $e->getMessage();
        } catch (\Throwable $e) {
            return 'Token validation error: ' . $e->getMessage();
        }

        return null;
    }
}
