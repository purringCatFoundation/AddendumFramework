<?php

declare(strict_types=1);

namespace PCF\Addendum\Action;

use JsonSerializable;

class HelloResponse implements JsonSerializable
{
    public function __construct(public string $message)
    {
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return ['message' => $this->message];
    }
}
