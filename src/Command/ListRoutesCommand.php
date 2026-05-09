<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use Ds\Vector;
use PCF\Addendum\Attribute\RateLimit;
use PCF\Addendum\Attribute\Route;
use PCF\Addendum\Http\Middleware\ClassAccessControlGuardianDefinition;
use PCF\Addendum\Http\Router;
use PCF\Addendum\Attribute\AccessControl as AccessControlAttribute;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(name: 'app:routes', description: 'List all registered routes with middleware and access control settings')]
class ListRoutesCommand extends Command
{
    public function __construct(
        private readonly Router $router
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'detailed',
                'd',
                InputOption::VALUE_NONE,
                'Show detailed middleware information'
            )
            ->addOption(
                'method',
                'm',
                InputOption::VALUE_REQUIRED,
                'Filter by HTTP method (GET, POST, DELETE, etc.)'
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Filter by path pattern (supports wildcards)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $detailed = $input->getOption('detailed');
        $methodFilter = $input->getOption('method');
        $pathFilter = $input->getOption('path');

        $allRoutes = $this->router->getRoutes()->getAllRoutes();

        if ($allRoutes->isEmpty()) {
            $output->writeln('<error>No routes registered</error>');
            return Command::FAILURE;
        }

        $routeCount = 0;

        foreach ($allRoutes as $method => $routes) {
            // Apply method filter
            if ($methodFilter && strtoupper($methodFilter) !== $method) {
                continue;
            }

            foreach ($routes as $route) {
                $reflection = new ReflectionClass($route->actionClass);
                $routeAttributes = $reflection->getAttributes(Route::class);

                if (empty($routeAttributes)) {
                    continue;
                }

                /** @var Route $routeAttr */
                $routeAttr = $routeAttributes[0]->newInstance();

                // Apply path filter
                if ($pathFilter && !$this->matchesPathPattern($routeAttr->path, $pathFilter)) {
                    continue;
                }

                $routeCount++;

                $this->displayRoute(
                    $output,
                    $method,
                    $routeAttr->path,
                    $route->actionClass,
                    $route->middlewares,
                    $reflection,
                    $detailed
                );
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Total routes: %d</info>', $routeCount));

        return Command::SUCCESS;
    }

    private function displayRoute(
        OutputInterface $output,
        string $method,
        string $path,
        string $actionClass,
        iterable $middlewares,
        ReflectionClass $reflection,
        bool $detailed
    ): void {
        $output->writeln('');
        $output->writeln(sprintf(
            '<fg=cyan;options=bold>%s</> <fg=yellow>%s</>',
            str_pad($method, 7),
            $path
        ));

        // Action class
        $actionShort = $this->getShortClassName($actionClass);
        $output->writeln(sprintf('  <fg=gray>Action:</> %s', $actionShort));

        // Access Control Guardians
        $guardians = $this->getAccessControlGuardians($reflection);
        if (!$guardians->isEmpty()) {
            $output->writeln(sprintf('  <fg=magenta>Guardians:</> %s', implode(', ', $guardians->toArray())));
        }

        // Rate Limit
        $rateLimit = $this->getRateLimit($reflection);
        if ($rateLimit) {
            $output->writeln(sprintf('  <fg=yellow>Rate Limit:</> %s', $rateLimit));
        }

        // Middleware
        $output->writeln('  <fg=blue>Middleware:</>');
        foreach ($middlewares as $middleware) {
            $middlewareShort = $this->getShortClassName($middleware->getClass());

            if ($detailed) {
                $options = $middleware->getOptions()->toArray();
                if (!empty($options)) {
                    $optionsStr = $this->formatOptions($options);
                    $output->writeln(sprintf('    - %s %s', $middlewareShort, $optionsStr));
                } else {
                    $output->writeln(sprintf('    - %s', $middlewareShort));
                }
            } else {
                $output->writeln(sprintf('    - %s', $middlewareShort));
            }
        }
    }

    /**
     * @return Vector<string>
     */
    private function getAccessControlGuardians(ReflectionClass $reflection): Vector
    {
        $guardians = new Vector();
        $attributes = $reflection->getAttributes(AccessControlAttribute::class);

        foreach ($attributes as $attribute) {
            $guardian = $attribute->newInstance()->getGuardianDefinition();

            if ($guardian instanceof ClassAccessControlGuardianDefinition) {
                $guardians->push($this->getShortClassName($guardian->guardianClass));
            } else {
                $guardians->push($guardian::class);
            }
        }

        return $guardians;
    }

    private function getRateLimit(ReflectionClass $reflection): ?string
    {
        $attributes = $reflection->getAttributes(RateLimit::class);

        if (empty($attributes)) {
            return null;
        }

        /** @var RateLimit $rateLimit */
        $rateLimit = $attributes[0]->newInstance();

        return sprintf(
            '%d requests per %ds (%s)',
            $rateLimit->maxAttempts,
            $rateLimit->windowSeconds,
            $rateLimit->scope
        );
    }

    private function getShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }

    private function formatOptions(array $options): string
    {
        $formatted = [];

        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $value = (string) $value;
                } else {
                    $value = get_class($value);
                }
            }

            $formatted[] = sprintf('<fg=gray>%s:</> %s', $key, $value);
        }

        return sprintf('(%s)', implode(', ', $formatted));
    }

    private function matchesPathPattern(string $path, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = str_replace(
            ['*', '/'],
            ['.*', '\/'],
            $pattern
        );

        return (bool) preg_match('#' . $regex . '#', $path);
    }
}
