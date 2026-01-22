<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Auth\AuthRepositoryInterface;
use PDO;

class AuthRepository implements AuthRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createUser(string $email, string $password): array
    {
        $hash = password_hash($password, \PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare('SELECT uuid, email FROM register_user(:email, :password)');
        $stmt->execute(['email' => $email, 'password' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row?: ['uuid' => null, 'email' => null];
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function latestPasswordHash(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT password FROM user_passwords WHERE user_id = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();
        return $hash ?: null;
    }
}
