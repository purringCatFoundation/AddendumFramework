<?php
declare(strict_types=1);

namespace PCF\Addendum\Application;

use Ds\Vector;
use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Http\RouterFactory;
use PCF\Addendum\Http\Routing\ActionScanner;
use PCF\Addendum\Http\Cache\HttpCacheRuntime;
use PCF\Addendum\Log\MonologFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;

class AppFactory
{
    /** @var Vector<ActionScanner> */
    private readonly Vector $scanners;

    /**
     * @param iterable<ActionScanner> $scanners
     */
    public function __construct(
        iterable $scanners,
        private readonly HttpCacheRuntime $httpCacheRuntime,
        private readonly ApplicationCacheConfiguration $cacheConfiguration
    ) {
        $this->scanners = $scanners instanceof Vector ? $scanners->copy() : new Vector($scanners);
    }

    public function create(): App
    {
        $router = new RouterFactory($this->scanners, $this->cacheConfiguration)->create();
        $environmentProvider = new SystemEnvironmentProvider();
        $logger = new MonologFactory($environmentProvider)->create();

        return new App($router, $logger, $this->httpCacheRuntime);
    }
}
