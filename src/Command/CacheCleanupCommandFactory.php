<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Application\Cache\ApplicationCacheConfigurationFactory;
use PCF\Addendum\Application\Cache\CompiledCacheCleaner;
use PCF\Addendum\Config\SystemEnvironmentProvider;

final class CacheCleanupCommandFactory implements FactoryInterface
{
    public function create(): CacheCleanupCommand
    {
        return new CacheCleanupCommand(
            new ApplicationCacheConfigurationFactory(new SystemEnvironmentProvider())->create(),
            new CompiledCacheCleaner()
        );
    }
}
