<?php
declare(strict_types=1);

namespace PCF\Addendum\Action\User;

use PCF\Addendum\Action\ActionFactoryInterface;

class PatchUserActionFactory implements ActionFactoryInterface
{
    public function create(): PatchUserAction
    {
        return new PatchUserAction();
    }
}
