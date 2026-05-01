<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Auth\AuthRepositoryInterface;

class AuthRepositoryFactory
{
    private static ?AuthRepositoryInterface $repo = null;

    public function __construct(private ?DbConnectionFactory $dbConnectionFactory = null)
    {
        $this->dbConnectionFactory ??= new DbConnectionFactory();
    }

    public function create(): AuthRepositoryInterface
    {
        if (self::$repo !== null) {
            return self::$repo;
        }

        $pdo = $this->dbConnectionFactory->create();
        self::$repo = new AuthRepository($pdo);

        return self::$repo;
    }
}
