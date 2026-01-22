<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionFactoryInterface;
use PCF\Addendum\Auth\AuthServiceFactory;

class DeleteSessionActionFactory implements ActionFactoryInterface
{
    public function create(): DeleteSessionAction
    {
        $authService = (new AuthServiceFactory())->create();

        return new DeleteSessionAction($authService);
    }
}
