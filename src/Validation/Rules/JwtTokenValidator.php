<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use Ds\Map;
use InvalidArgumentException;
use PCF\Addendum\Auth\Jwt;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Validation\AbstractRequestValidator;
use PCF\Addendum\Validation\RequestAttributeProviderValidatorInterface;
use RuntimeException;

final class JwtTokenValidator extends AbstractRequestValidator implements RequestAttributeProviderValidatorInterface
{
    public function __construct(
        private readonly JwtConfig $config,
        private readonly TokenValidationRepository $tokenValidationRepository,
        private readonly string $requiredTokenType
    ) {
    }

    public function validate(mixed $value): ?string
    {
        if (empty($value)) {
            return 'Token is required';
        }

        $token = trim((string) $value);
        if ($token === '') {
            return 'Token is required';
        }

        try {
            $payload = Jwt::decode($token, $this->config->secret);

            if ($payload->tokenType !== $this->requiredTokenType) {
                return "Invalid token type, expected '{$this->requiredTokenType}'";
            }

            if (!$this->tokenValidationRepository->isTokenValid($payload->sub, $payload->iat)) {
                return 'Token has been revoked';
            }
        } catch (InvalidArgumentException $exception) {
            return 'Invalid token: ' . $exception->getMessage();
        } catch (RuntimeException $exception) {
            return 'Configuration error: ' . $exception->getMessage();
        } catch (\Throwable $exception) {
            return 'Token validation error: ' . $exception->getMessage();
        }

        return null;
    }

    public function requestAttributes(mixed $value): Map
    {
        return new Map(['jwt_token' => trim((string) $value)]);
    }
}
