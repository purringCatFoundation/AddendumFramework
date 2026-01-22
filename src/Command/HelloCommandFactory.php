<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Command\FactoryInterface;

class HelloCommandFactory implements FactoryInterface
{
    public function create(): HelloCommand
    {
        return new HelloCommand();
    }
}
