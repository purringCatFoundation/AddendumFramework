<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use Ds\Vector;
use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\CompiledCacheInspector;
use PCF\Addendum\Http\RegisteredRoute;
use PCF\Addendum\Http\RouteCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cache:debug', description: 'Inspect compiled HTTP application cache')]
final class CacheDebugCommand extends Command
{
    public function __construct(
        private readonly ApplicationCacheConfiguration $configuration,
        private readonly CompiledCacheInspector $inspector
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print cache status as JSON')
            ->addOption('details', 'd', InputOption::VALUE_NONE, 'Show route middleware and policy details')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Show only routes matching route path or request path')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Return failure when compiled cache is incomplete or invalid');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = $this->inspector->inspect($this->configuration);
        $routes = $this->hasValidRoutes($status) ? $this->loadRoutes() : null;
        $pathFilter = $input->getOption('path');
        $pathFilter = is_string($pathFilter) && trim($pathFilter) !== '' ? trim($pathFilter) : null;
        $routeRows = $routes instanceof RouteCollection ? $this->routeRows($routes, $pathFilter) : new Vector();

        if ($input->getOption('json')) {
            $routesList = [];

            foreach ($routeRows as $row) {
                $routesList[] = $this->routeDetails($row->method, $row->route);
            }

            $status['routesList'] = $routesList;
            $output->writeln(json_encode($status, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return $this->isHealthy($status) || !$input->getOption('strict') ? Command::SUCCESS : Command::FAILURE;
        }

        if (!$routes instanceof RouteCollection) {
            $output->writeln(sprintf('APP_CACHE: %s', $status['mode']));
            $output->writeln(sprintf('APP_ENV: %s', $status['environment']));
            $output->writeln(sprintf('APP_CACHE_DIR: %s', $status['compiledDirectory']));
            $this->printFileStatus($output, 'routes.php', $status['routes']);
            $this->printFileStatus($output, 'metadata.php', $status['metadata']);
            $this->printFileStatus($output, 'app.php', $status['app']);

            return $this->isHealthy($status) || !$input->getOption('strict') ? Command::SUCCESS : Command::FAILURE;
        }

        if ($routeRows->isEmpty()) {
            $output->writeln($pathFilter !== null ? sprintf('No routes matching %s', $pathFilter) : 'No routes registered');

            return $this->isHealthy($status) || !$input->getOption('strict') ? Command::SUCCESS : Command::FAILURE;
        }

        foreach ($routeRows as $row) {
            $route = $row->route;
            $method = $row->method;

            if ($input->getOption('details')) {
                $output->writeln(sprintf('%s %s => %s', $method, $route->path, $route->actionClass));
                $output->writeln(json_encode(
                    $this->routeDetails($method, $route),
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ));

                continue;
            }

            $output->writeln(sprintf('%s %s', $method, $route->path));
        }

        return $this->isHealthy($status) || !$input->getOption('strict') ? Command::SUCCESS : Command::FAILURE;
    }

    private function loadRoutes(): ?RouteCollection
    {
        $factory = require $this->configuration->routesFile();

        if (!is_callable($factory)) {
            return null;
        }

        $routes = $factory();

        return $routes instanceof RouteCollection ? $routes : null;
    }

    /** @return Vector<CacheDebugRouteRow> */
    private function routeRows(RouteCollection $routes, ?string $pathFilter): Vector
    {
        $rows = new Vector();

        foreach ($routes->getAllRoutes() as $method => $registeredRoutes) {
            foreach ($registeredRoutes as $route) {
                if ($pathFilter !== null && !$this->matchesPathFilter($route, $pathFilter)) {
                    continue;
                }

                $rows->push(new CacheDebugRouteRow($method, $route));
            }
        }

        $rows->sort(static fn(CacheDebugRouteRow $left, CacheDebugRouteRow $right): int => [$left->route->path, $left->method]
            <=> [$right->route->path, $right->method]);

        return $rows;
    }

    private function matchesPathFilter(RegisteredRoute $route, string $pathFilter): bool
    {
        return $route->path === $pathFilter || $route->matches($pathFilter) !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function routeDetails(string $method, RegisteredRoute $route): array
    {
        return [
            'method' => $method,
            'path' => $route->path,
            'pattern' => $route->pattern,
            'action' => $route->actionClass,
            'middleware' => (function () use ($route): array {
                $middlewareRows = [];

                foreach ($route->middlewares as $middleware) {
                    $middlewareRows[] = [
                        'class' => $middleware->getClass(),
                        'options' => $middleware->getOptions()->toArray(),
                    ];
                }

                return $middlewareRows;
            })(),
            'policies' => (function () use ($route): array {
                $policyRows = [];

                foreach ($route->resourcePolicies->all() as $policy) {
                    $policyRows[] = [
                        'mode' => $policy->mode->value,
                        'maxAge' => $policy->maxAge,
                        'resource' => $policy->resource,
                        'idAttribute' => $policy->idAttribute,
                        'cacheErrors' => $policy->cacheErrors,
                    ];
                }

                return $policyRows;
            })(),
        ];
    }

    /**
     * @param array<string, mixed> $status
     */
    private function printFileStatus(OutputInterface $output, string $name, array $status): void
    {
        $state = $status['valid'] ? 'valid' : ($status['exists'] ? 'invalid' : 'missing');
        $output->writeln(sprintf('%s: %s (%s)', $name, $state, $status['path']));

        if (isset($status['data']['routeCount'])) {
            $output->writeln(sprintf('routes: %d', $status['data']['routeCount']));
        }

        if ($status['error'] !== null) {
            $output->writeln(sprintf('error: %s', $status['error']));
        }
    }

    /**
     * @param array<string, mixed> $status
     */
    private function isHealthy(array $status): bool
    {
        return $this->hasValidRoutes($status)
            && ($status['metadata']['valid'] ?? false)
            && ($status['app']['valid'] ?? false);
    }

    /**
     * @param array<string, mixed> $status
     */
    private function hasValidRoutes(array $status): bool
    {
        return $status['routes']['valid'] ?? false;
    }
}
