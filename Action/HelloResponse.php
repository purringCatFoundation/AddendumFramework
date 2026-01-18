<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

use JsonSerializable;

class HelloResponse implements JsonSerializable
{
    public function __construct(public string $message)
    {
    }

    public function jsonSerialize(): array
    {
        return ['message' => $this->message];
    }
}
