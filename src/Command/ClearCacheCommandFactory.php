<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Command\FactoryInterface;
use PCF\Addendum\Cache\RedisCacheFactory;

class ClearCacheCommandFactory implements FactoryInterface
{
    public function create(): ClearCacheCommand
    {
        $cache = new RedisCacheFactory()->create();
        return new ClearCacheCommand($cache);
    }
}
