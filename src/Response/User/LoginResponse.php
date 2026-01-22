<?php
declare(strict_types=1);

namespace PCF\Addendum\Response\User;

use JsonSerializable;

class LoginResponse implements JsonSerializable
{
    public function __construct(public string $accessToken, public string $refreshToken)
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
        ];
    }
}
