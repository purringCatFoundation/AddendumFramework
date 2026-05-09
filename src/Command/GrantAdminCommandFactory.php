<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Repository\User\AdminRepositoryFactory;
use PCF\Addendum\Repository\User\AuthRepositoryFactory;

final class GrantAdminCommandFactory
{
    public function create(): GrantAdminCommand
    {
        $authRepositoryFactory = new AuthRepositoryFactory(new DbConnectionFactory());
        $authRepository = $authRepositoryFactory->create();

        $adminRepositoryFactory = new AdminRepositoryFactory();
        $adminRepository = $adminRepositoryFactory->create();

        return new GrantAdminCommand($authRepository, $adminRepository);
    }
}
