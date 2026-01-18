<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Auth;

use Pradzikowski\Framework\Action\FactoryInterface;
use Pradzikowski\Framework\Database\DbConnectionFactory;

class TokenValidationRepositoryFactory implements FactoryInterface
{
    public function __construct(private ?DbConnectionFactory $dbConnectionFactory = null)
    {
        $this->dbConnectionFactory ??= new DbConnectionFactory();
    }

    public function create(): TokenValidationRepository
    {
        $pdo = $this->dbConnectionFactory->create();
        return new TokenValidationRepository($pdo);
    }
}