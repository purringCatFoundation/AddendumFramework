<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Config\SystemEnvironmentProvider;

class ClearReverseProxyCacheCommandFactory
{
    public function create(): ClearReverseProxyCacheCommand
    {
        $envProvider = new SystemEnvironmentProvider();
        $url = $envProvider->get('REVERSE_PROXY_URL', '');

        return new ClearReverseProxyCacheCommand($url);
    }
}
