<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionFactoryInterface;

class DeleteUserActionFactory implements ActionFactoryInterface
{
    public function create(): DeleteUserAction
    {
        return new DeleteUserAction();
    }
}
