<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Config\SystemEnvironmentProvider;

class ClearHttpCacheCommandFactory
{
    public function create(): ClearHttpCacheCommand
    {
        $envProvider = new SystemEnvironmentProvider();
        $url = $envProvider->get('HTTP_CACHE_URL', '');

        return new ClearHttpCacheCommand($url);
    }
}
