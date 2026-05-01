<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Action\FactoryInterface;
use PCF\Addendum\Database\DbConnectionFactory;

class ApplicationTokenRepositoryFactory implements FactoryInterface
{
    public function create(): ApplicationTokenRepository
    {
        $pdo = new DbConnectionFactory()->create();

        return new ApplicationTokenRepository($pdo);
    }
}
