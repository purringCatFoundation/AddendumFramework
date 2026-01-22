<?php
declare(strict_types=1);

namespace PCF\Addendum\Action;

interface FactoryInterface
{
    public function create(): mixed;
}