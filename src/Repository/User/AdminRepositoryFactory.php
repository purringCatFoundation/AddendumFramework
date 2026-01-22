<?php
declare(strict_types=1);

namespace PCF\Addendum\Repository\User;

use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Repository\User\AdminRepository;

final class AdminRepositoryFactory
{
    public function create(): AdminRepository
    {
        $dbFactory = new DbConnectionFactory();
        $db = $dbFactory->create();

        return new AdminRepository($db);
    }
}
