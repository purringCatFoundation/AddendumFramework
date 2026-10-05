<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Database\DbConnectionFactory;

final class RevokeApplicationTokensCommandFactory
{
    public function create(): RevokeApplicationTokensCommand
    {
        return new RevokeApplicationTokensCommand(
            new TokenValidationRepositoryFactory(new DbConnectionFactory())->create()
        );
    }
}
