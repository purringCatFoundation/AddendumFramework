<?php
declare(strict_types=1);

namespace PCF\Addendum\Util;

use Symfony\Component\Finder\Finder;

class FinderFactory
{
    public function create(): Finder
    {
        return new Finder();
    }
}