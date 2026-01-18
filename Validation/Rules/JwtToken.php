<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Validation\Rules;

use Pradzikowski\Framework\Auth\Jwt;
use Pradzikowski\Framework\Auth\TokenType;
use Pradzikowski\Framework\Auth\TokenValidationRepositoryFactory;
use Pradzikowski\Framework\Config\JwtConfigFactory;
use CitiesRpg\ApiBackend\Validation\AbstractRequestValidator;
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
            $tokenValidationRepository = new TokenValidationRepositoryFactory()->create();

            $payload = Jwt::decode($token, $jwtConfig->secret);

            if ($this->requiredTokenType !== null && $payload->tokenType !== $this->requiredTokenType) {
                return "Invalid token type, expected '{$this->requiredTokenType}'";
            }

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