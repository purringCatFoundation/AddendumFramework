<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\CompiledHttpApplicationCache;
use PCF\Addendum\Application\Cache\CompiledHttpApplicationGenerator;
use PCF\Addendum\Application\Cache\PhpFileWriter;
use PCF\Addendum\Http\Routing\ActionScanner;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionCache;
use PCF\Addendum\Http\Routing\CompiledRouteCollectionGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cache:warmup', description: 'Build compiled HTTP application cache')]
final class CacheWarmupCommand extends Command
{
    public function __construct(
        private readonly ApplicationCacheConfiguration $configuration
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'action-path',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Action directory to scan. Can be provided multiple times.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paths = $input->getOption('action-path');

        if ($paths === []) {
            $paths = [getcwd() . '/src/Action'];
        }

        $scanners = array_map(
            static fn(string $path): ActionScanner => new ActionScanner($path),
            array_values(array_filter($paths, static fn(string $path): bool => trim($path) !== ''))
        );

        $routes = new CompiledRouteCollectionCache(
            $this->configuration,
            new CompiledRouteCollectionGenerator(),
            new PhpFileWriter()
        )->warmup($scanners);
        new CompiledHttpApplicationCache(
            $this->configuration,
            new CompiledHttpApplicationGenerator(),
            new PhpFileWriter()
        )->warmup();
        $routeCount = 0;

        foreach ($routes->getAllRoutes() as $registeredRoutes) {
            $routeCount += count($registeredRoutes);
        }

        $output->writeln(sprintf('Compiled HTTP routes: %d', $routeCount));
        $output->writeln(sprintf('Routes cache: %s', $this->configuration->routesFile()));
        $output->writeln(sprintf('App cache: %s', $this->configuration->appFile()));

        return Command::SUCCESS;
    }
}
