<?php
declare(strict_types=1);

namespace PCF\Addendum\Database;

use PCF\Addendum\Action\FactoryInterface;
use PDO;
use RuntimeException;

class DbConnectionFactory implements FactoryInterface
{
    public function create(): PDO
    {
        $dsn = $this->getDsn();
        $user = $this->getEnvVar('POSTGRES_USER', 'app');
        $pass = $this->getEnvVar('POSTGRES_PASSWORD', '');
        
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    }
    
    private function getDsn(): string
    {
        $dsn = $this->getEnvVar('DB_DSN');

        if ($dsn !== null) {
            if (!str_starts_with($dsn, 'pgsql:')) {
                throw new RuntimeException('Only PostgreSQL DSNs are supported');
            }

            return $dsn;
        }

        $host = $this->getEnvVar('POSTGRES_HOST', 'localhost');
        $port = $this->getEnvVar('POSTGRES_PORT', '5432');
        $database = $this->getEnvVar('POSTGRES_DB', 'app');

        return sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }

    private function getEnvVar(string $name, ?string $default = null): ?string
    {
        $value = $_ENV[$name] ?? getenv($name);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
