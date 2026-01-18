<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

use Pradzikowski\Framework\Action\ActionFactoryInterface;
use Pradzikowski\Framework\Action\GetHelloAction;

class GetHelloActionFactory implements ActionFactoryInterface
{
    public function create(): GetHelloAction
    {
        return new GetHelloAction();
    }
}
