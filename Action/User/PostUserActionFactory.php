<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionFactoryInterface;
use Pradzikowski\Framework\Auth\AuthServiceFactory;

class PostUserActionFactory implements ActionFactoryInterface
{
    public function create(): PostUserAction
    {
        $service = new AuthServiceFactory()->create();
        return new PostUserAction($service);
    }
}
