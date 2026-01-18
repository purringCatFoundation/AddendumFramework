<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionFactoryInterface;
use Pradzikowski\Framework\Auth\AuthServiceFactory;

class GetRefreshSessionActionFactory implements ActionFactoryInterface
{
    public function create(): GetRefreshSessionAction
    {
        $service = new AuthServiceFactory()->create();
        return new GetRefreshSessionAction($service);
    }
}
