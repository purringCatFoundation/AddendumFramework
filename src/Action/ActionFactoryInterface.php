<?php
declare(strict_types=1);

namespace PCF\Addendum\Action;

interface ActionFactoryInterface
{
    public function create(): ActionInterface;
}
