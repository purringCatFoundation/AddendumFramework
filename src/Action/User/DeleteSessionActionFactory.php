<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionFactoryInterface;
use PCF\Addendum\Auth\AuthServiceFactory;

class DeleteSessionActionFactory implements ActionFactoryInterface
{
    public function create(): DeleteSessionAction
    {
        $authService = AuthServiceFactory::fromEnvironment()->create();

        return new DeleteSessionAction($authService);
    }
}
