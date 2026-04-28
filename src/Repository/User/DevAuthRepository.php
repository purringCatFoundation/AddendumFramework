<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use ArrayObject;
use PCF\Addendum\Auth\AuthRepositoryInterface;
use PCF\Addendum\Auth\RegisteredUser;
use PCF\Addendum\Auth\UserIdentity;
use PCF\Addendum\Util\Uuid;

class DevAuthRepository implements AuthRepositoryInterface
{
    /** @var ArrayObject<int, DevUserAccount> */
    private ArrayObject $users;
    private int $nextId = 1;

    public function __construct(array $seedUsers = [])
    {
        $this->users = new ArrayObject();

        foreach ($seedUsers as $user) {
            $id = (int) ($user['id'] ?? 0);
            if ($id > 0) {
                $this->users[$id] = new DevUserAccount(
                    id: $id,
                    uuid: (string) $user['uuid'],
                    email: (string) $user['email'],
                    passwordHash: (string) $user['password']
                );
                $this->nextId = max($this->nextId, $id + 1);
            }
        }
    }

    public function createUser(string $email, string $password): RegisteredUser
    {
        $id = $this->nextId++;
        $uuid = Uuid::v4();
        $algo = defined('PASSWORD_ARGON2ID') ? \PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hash = password_hash($password, $algo);
        $this->users[$id] = new DevUserAccount($id, $uuid, $email, $hash);

        return new RegisteredUser($uuid, $email);
    }

    public function findUserByEmail(string $email): ?UserIdentity
    {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return new UserIdentity($user->id, $user->uuid, $user->email);
            }
        }
        return null;
    }

    public function latestPasswordHash(int $userId): ?string
    {
        $user = $this->users[$userId] ?? null;

        return $user instanceof DevUserAccount ? $user->passwordHash : null;
    }
}
