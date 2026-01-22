<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionFactoryInterface;

class GetUserActionFactory implements ActionFactoryInterface
{
    public function create(): GetUserAction
    {
        return new GetUserAction();
    }
}
