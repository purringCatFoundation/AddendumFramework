<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

final readonly class RegisteredUser
{
    public function __construct(
        public string $uuid,
        public string $email
    ) {
    }
}
