<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Auth;

interface AuthRepositoryInterface
{
    public function createUser(string $email, string $password): array;
    public function findUserByEmail(string $email): ?array;
    public function latestPasswordHash(int $userId): ?string;
}
