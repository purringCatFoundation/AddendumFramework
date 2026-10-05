<?php

declare(strict_types=1);

namespace PCF\Addendum\Response\User;

use JsonSerializable;

class RefreshResponse implements JsonSerializable
{
    public function __construct(public string $accessToken, public string $refreshToken)
    {
    }

    /** @return array{access_token: string, refresh_token: string} */
    public function jsonSerialize(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
        ];
    }
}
