<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

final readonly class UserIdentity
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $email
    ) {
    }
}
