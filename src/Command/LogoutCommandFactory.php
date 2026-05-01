<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Database\DbConnectionFactory;

class LogoutCommandFactory
{
    public function create(): LogoutCommand
    {
        $repository = new TokenValidationRepositoryFactory(
            new DbConnectionFactory()
        )->create();

        return new LogoutCommand($repository);
    }
}
