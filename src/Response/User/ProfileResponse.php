<?php
declare(strict_types=1);

namespace PCF\Addendum\Response\User;

use JsonSerializable;

class ProfileResponse implements JsonSerializable
{
    public function __construct(public ?string $uuid)
    {
    }

    public function jsonSerialize(): array
    {
        return ['uuid' => $this->uuid];
    }
}
