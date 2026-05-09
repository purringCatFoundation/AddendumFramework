<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Application\Cache\ApplicationCacheConfigurationFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;

final class CacheWarmupCommandFactory implements FactoryInterface
{
    public function create(): CacheWarmupCommand
    {
        return new CacheWarmupCommand(new ApplicationCacheConfigurationFactory(new SystemEnvironmentProvider())->create());
    }
}
