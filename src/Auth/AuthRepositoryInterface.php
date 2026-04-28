<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

interface AuthRepositoryInterface
{
    public function createUser(string $email, string $password): RegisteredUser;
    public function findUserByEmail(string $email): ?UserIdentity;
    public function latestPasswordHash(int $userId): ?string;
}
