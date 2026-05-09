<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use Ds\Vector;
use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\PhpFileWriter;
use PCF\Addendum\Http\Routing\ActionScanner;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionCache;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionGenerator;

class RouterFactory
{
    /** @var Vector<ActionScanner> */
    private readonly Vector $scanners;

    /**
     * @param iterable<ActionScanner> $scanners
     */
    public function __construct(
        iterable $scanners,
        private readonly ApplicationCacheConfiguration $cacheConfiguration
    ) {
        $this->scanners = $scanners instanceof Vector ? $scanners->copy() : new Vector($scanners);
    }

    public function create(): Router
    {
        $routes = new CompiledRouteCollectionCache(
            $this->cacheConfiguration,
            new CompiledRouteCollectionGenerator(),
            new PhpFileWriter()
        )->loadOrBuild($this->scanners);

        return new Router($routes);
    }
}
