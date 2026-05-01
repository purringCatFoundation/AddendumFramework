<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionFactoryInterface;
use PCF\Addendum\Auth\AuthServiceFactory;

class PostUserActionFactory implements ActionFactoryInterface
{
    public function create(): PostUserAction
    {
        $service = new AuthServiceFactory()->create();

        return new PostUserAction($service);
    }
}
