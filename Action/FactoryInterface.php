<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

interface FactoryInterface
{
    public function create(): mixed;
}