<?php

declare(strict_types=1);

namespace PCF\Addendum\Application;

use Ds\Vector;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Application\Cache\ApplicationCacheConfigurationFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Http\Cache\HttpCacheBackendProviderFactory;
use PCF\Addendum\Http\Cache\HttpCacheConfigurationFactory;
use PCF\Addendum\Http\Cache\HttpCacheRuntimeFactory;
use PCF\Addendum\Http\Routing\ActionScanner;
use Psr\Http\Message\ResponseInterface;

final class HttpApplicationFactory
{
    /** @param callable(): iterable<string> $actionPaths */
    public function create(callable $actionPaths): App
    {
        $environmentProvider = new SystemEnvironmentProvider();
        $configuration = new ApplicationCacheConfigurationFactory($environmentProvider)->create();
        $scanners = new Vector();
        if (
            !$configuration->isEnabled()
            || $configuration->shouldRefreshOnRequest()
            || !is_file($configuration->routesFile())
        ) {
            foreach ($actionPaths() as $path) {
                $scanners->push(new ActionScanner($path));
            }
        }
        $runtime = new HttpCacheRuntimeFactory(
            new HttpCacheConfigurationFactory($environmentProvider),
            new HttpCacheBackendProviderFactory()
        )->create();

        return new AppFactory($scanners, $runtime, $configuration)->create();
    }

    public function handleGlobals(App $app): ResponseInterface
    {
        return $app->handle(ServerRequest::fromGlobals());
    }
}
