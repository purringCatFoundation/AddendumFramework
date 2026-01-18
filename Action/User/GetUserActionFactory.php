<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionFactoryInterface;

class GetUserActionFactory implements ActionFactoryInterface
{
    public function create(): GetUserAction
    {
        return new GetUserAction();
    }
}
