<?php
declare(strict_types=1);

namespace PCF\Addendum\Response\User;

use JsonSerializable;

class RevokeTokensResponse implements JsonSerializable
{
    public function __construct(public bool $success)
    {
    }

    public function jsonSerialize(): array
    {
        return ['success' => $this->success];
    }
}
