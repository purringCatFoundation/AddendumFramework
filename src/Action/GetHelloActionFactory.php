<?php
declare(strict_types=1);

namespace PCF\Addendum\Action;

use PCF\Addendum\Action\ActionFactoryInterface;
use PCF\Addendum\Action\GetHelloAction;

class GetHelloActionFactory implements ActionFactoryInterface
{
    public function create(): GetHelloAction
    {
        return new GetHelloAction();
    }
}
