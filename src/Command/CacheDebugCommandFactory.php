<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Application\Cache\ApplicationCacheConfigurationFactory;
use PCF\Addendum\Application\Cache\CompiledCacheInspector;
use PCF\Addendum\Config\SystemEnvironmentProvider;

final class CacheDebugCommandFactory implements FactoryInterface
{
    public function create(): CacheDebugCommand
    {
        return new CacheDebugCommand(
            new ApplicationCacheConfigurationFactory(new SystemEnvironmentProvider())->create(),
            new CompiledCacheInspector()
        );
    }
}
