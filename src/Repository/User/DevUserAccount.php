<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

final readonly class DevUserAccount
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $email,
        public string $passwordHash
    ) {
    }
}
