<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Application;

use Pradzikowski\Framework\Http\RouterFactory;
use Pradzikowski\Framework\Http\Routing\ActionScanner;
use Pradzikowski\Framework\Http\Routing\AccessControlMiddlewareProvider;
use Pradzikowski\Framework\Log\MonologFactory;
use Pradzikowski\Framework\Config\SystemEnvironmentProvider;

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
