<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\Admin;

use PCF\Addendum\Action\ActionFactoryInterface;
use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Database\DbConnectionFactory;

class DeleteAdminTokensActionFactory implements ActionFactoryInterface
{
    public function create(): DeleteAdminTokensAction
    {
        return new DeleteAdminTokensAction(
            new TokenValidationRepositoryFactory(
                new DbConnectionFactory()
            )->create()
        );
    }
}
