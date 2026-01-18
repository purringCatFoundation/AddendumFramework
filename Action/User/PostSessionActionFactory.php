<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionFactoryInterface;
use Pradzikowski\Framework\Auth\AuthServiceFactory;

class PostSessionActionFactory implements ActionFactoryInterface
{
    public function create(): PostSessionAction
    {
        $service = new AuthServiceFactory()->create();
        return new PostSessionAction($service);
    }
}
