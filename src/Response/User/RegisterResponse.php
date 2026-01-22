<?php
declare(strict_types=1);

namespace PCF\Addendum\Response\User;

use JsonSerializable;
use PCF\Addendum\Response\HttpStatusAware;

class RegisterResponse implements JsonSerializable, HttpStatusAware
{
    public function __construct(public string $uuid, public string $email)
    {
    }

    public function getStatusCode(): int
    {
        return 201;
    }

    public function jsonSerialize(): array
    {
        return ['uuid' => $this->uuid, 'email' => $this->email];
    }
}
