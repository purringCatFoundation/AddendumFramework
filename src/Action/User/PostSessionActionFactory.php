<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionFactoryInterface;
use PCF\Addendum\Auth\AuthServiceFactory;

class PostSessionActionFactory implements ActionFactoryInterface
{
    public function create(): PostSessionAction
    {
        $service = (new AuthServiceFactory())->create();

        return new PostSessionAction($service);
    }
}
