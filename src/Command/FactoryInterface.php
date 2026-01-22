<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use Symfony\Component\Console\Command\Command;

interface FactoryInterface
{
    public function create(): Command;
}