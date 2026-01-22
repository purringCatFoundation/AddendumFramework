<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Auth\AuthRepositoryInterface;
use PCF\Addendum\Util\Uuid;

class DevAuthRepository implements AuthRepositoryInterface
{
    private array $users = [];
    private int $nextId = 1;

    public function __construct(array $seedUsers = [])
    {
        foreach ($seedUsers as $user) {
            $id = (int) ($user['id'] ?? 0);
            if ($id > 0) {
                $this->users[$id] = $user;
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
    }

    public function createUser(string $email, string $password): array
    {
        $id = $this->nextId++;
        $uuid = Uuid::v4();
        $algo = defined('PASSWORD_ARGON2ID') ? \PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hash = password_hash($password, $algo);
        $this->users[$id] = [
            'id' => $id,
            'uuid' => $uuid,
            'email' => $email,
            'password' => $hash,
        ];
        return ['uuid' => $uuid, 'email' => $email];
    }

    public function findUserByEmail(string $email): ?array
    {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return ['id' => $user['id'], 'uuid' => $user['uuid'], 'email' => $user['email']];
            }
        }
        return null;
    }

    public function latestPasswordHash(int $userId): ?string
    {
        return $this->users[$userId]['password'] ?? null;
    }
}
