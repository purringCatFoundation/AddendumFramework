<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Repository\User\AuthRepository;
use PCF\Addendum\Auth\AuthRepositoryInterface;
use PCF\Addendum\Repository\User\DevAuthRepository;

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

        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'prod';

        if ($env === 'dev') {
            $file = dirname(__DIR__, 2) . '/dev/dev_accounts.php';
            $users = file_exists($file) ? require $file : [];
            self::$repo = new DevAuthRepository($users);
        } else {
            $pdo = $this->dbConnectionFactory->create();
            self::$repo = new AuthRepository($pdo);
        }

        return self::$repo;
    }
}
