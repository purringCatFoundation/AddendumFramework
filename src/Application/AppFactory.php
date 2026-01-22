<?php
declare(strict_types=1);

namespace PCF\Addendum\Application;

use PCF\Addendum\Http\RouterFactory;
use PCF\Addendum\Http\Routing\ActionScanner;
use PCF\Addendum\Http\Routing\AccessControlMiddlewareProvider;
use PCF\Addendum\Log\MonologFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;

class AppFactory
{
    /**
     * @param ActionScanner[] $scanners
     */
    public function __construct(
        private readonly array $scanners
    ) {
    }

    public function create(): App
    {
        $router = new RouterFactory($this->scanners)->create();
        $environmentProvider = new SystemEnvironmentProvider();
        $logger = new MonologFactory($environmentProvider)->create();

        return new App($router, $logger);
    }
}
