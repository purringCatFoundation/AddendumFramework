<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\ApplicationCacheMode;
use PCF\Addendum\Application\Cache\PhpFileWriter;
use PCF\Addendum\Http\RouteCollection;
use RuntimeException;
use Throwable;

final readonly class CompiledRouteCollectionCache
{
    public function __construct(
        private ApplicationCacheConfiguration $configuration,
        private CompiledRouteCollectionGenerator $generator,
        private PhpFileWriter $writer
    ) {
    }

    /**
     * @param iterable<ActionScanner> $scanners
     */
    public function loadOrBuild(iterable $scanners): RouteCollection
    {
        if (!$this->configuration->isEnabled()) {
            return $this->build($scanners);
        }

        if ($this->configuration->shouldRefreshOnRequest() || !is_file($this->configuration->routesFile())) {
            return $this->warmup($scanners);
        }

        try {
            return $this->load();
        } catch (Throwable $exception) {
            if ($this->configuration->mode === ApplicationCacheMode::REQUIRED) {
                throw $exception;
            }

            return $this->warmup($scanners);
        }
    }

    /**
     * @param iterable<ActionScanner> $scanners
     */
    public function warmup(iterable $scanners): RouteCollection
    {
        $routes = $this->build($scanners);

        $this->writer->write($this->configuration->routesFile(), $this->generator->generate($routes));
        $this->writer->write($this->configuration->metadataFile(), $this->metadataCode($routes));

        return $routes;
    }

    public function load(): RouteCollection
    {
        $factory = require $this->configuration->routesFile();

        if (!is_callable($factory)) {
            throw new RuntimeException(sprintf('Compiled routes file "%s" must return a callable', $this->configuration->routesFile()));
        }

        $routes = $factory();

        if (!$routes instanceof RouteCollection) {
            throw new RuntimeException(sprintf('Compiled routes file "%s" must return RouteCollection', $this->configuration->routesFile()));
        }

        return $routes;
    }

    /**
     * @param iterable<ActionScanner> $scanners
     */
    private function build(iterable $scanners): RouteCollection
    {
        return new RouteCollectionBuilder($scanners, new MiddlewareStackBuilder(), new RoutePatternCompiler())->build();
    }

    private function metadataCode(RouteCollection $routes): string
    {
        $routeCount = 0;

        foreach ($routes->getAllRoutes() as $registeredRoutes) {
            $routeCount += count($registeredRoutes);
        }

        return "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export([
            'builtAt' => gmdate('c'),
            'routeCount' => $routeCount,
        ], true) . ";\n";
    }
}
