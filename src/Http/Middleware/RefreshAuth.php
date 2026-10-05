<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\TokenPayload;
use PCF\Addendum\Auth\TokenType;

final class RefreshAuth extends AbstractTokenAuth
{
    protected function tokenError(TokenPayload $payload, string $token): ?string
    {
        return $payload->getTokenType() === TokenType::USER_REFRESH ? null : 'Invalid token type';
    }
}
