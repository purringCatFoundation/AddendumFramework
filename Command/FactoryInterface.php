<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Command;

use Symfony\Component\Console\Command\Command;

interface FactoryInterface
{
    public function create(): Command;
}