<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Database;

use Pradzikowski\Framework\Action\FactoryInterface;
use PDO;

class DbConnectionFactory implements FactoryInterface
{
    public function create(): PDO
    {
        $dsn = $this->getEnvVar('DB_DSN');
        $user = $this->getEnvVar('DB_USER');
        $pass = $this->getEnvVar('DB_PASS');
        
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    }
    
    private function getEnvVar(string $name): string|false
    {
        return $_ENV[$name] ?? getenv($name);
    }
}