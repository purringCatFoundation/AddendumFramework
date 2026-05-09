<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionFactoryInterface;
use PCF\Addendum\Auth\AuthServiceFactory;

class PostRefreshSessionActionFactory implements ActionFactoryInterface
{
    public function create(): PostRefreshSessionAction
    {
        $service = AuthServiceFactory::fromEnvironment()->create();

        return new PostRefreshSessionAction($service);
    }
}
