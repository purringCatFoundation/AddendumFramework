<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\ApplicationTokenValidator;
use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Auth\TokenValidationRepository;

class Auth extends AbstractTokenAuth
{
    public function __construct(
        string $publicKeyPath,
        TokenValidationRepository $tokenValidationRepository,
        private readonly ApplicationTokenValidator $applicationTokenValidator
    ) {
        parent::__construct($publicKeyPath, $tokenValidationRepository);
    }

    protected function tokenError(TokenPayload $payload, string $token): ?string
    {
        $type = $payload->getTokenType();
        if (!in_array($type, [TokenType::USER, TokenType::ADMIN, TokenType::APPLICATION], true)) {
            return 'Invalid token type';
        }

        if ($type === TokenType::APPLICATION && !$this->applicationTokenValidator->isKnown($payload->jti, $token)) {
            return 'Unknown application token';
        }

        return null;
    }
}
