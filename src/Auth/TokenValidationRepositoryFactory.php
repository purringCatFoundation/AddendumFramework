<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use PCF\Addendum\Action\FactoryInterface;
use PCF\Addendum\Database\DbConnectionFactory;

class TokenValidationRepositoryFactory implements FactoryInterface
{
    public function __construct(private DbConnectionFactory $dbConnectionFactory)
    {
    }

    public function create(): TokenValidationRepository
    {
        $pdo = $this->dbConnectionFactory->create();
        return new TokenValidationRepository($pdo);
    }
}
