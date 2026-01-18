<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

interface ActionFactoryInterface
{
    public function create(): ActionInterface;
}
