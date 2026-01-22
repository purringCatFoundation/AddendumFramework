<?php
declare(strict_types=1);

namespace PCF\Addendum\Response\User;

use JsonSerializable;
use PCF\Addendum\Response\HttpStatusAware;

class LogoutResponse implements JsonSerializable, HttpStatusAware
{
    public function __construct(
        public bool $success,
        public ?string $message = null
    ) {
    }

    public function getStatusCode(): int
    {
        return 204;
    }

    public function jsonSerialize(): array
    {
        return ['success' => $this->success];
    }
}
