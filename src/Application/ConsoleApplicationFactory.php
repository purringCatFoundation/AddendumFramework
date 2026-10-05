<?php

declare(strict_types=1);

namespace PCF\Addendum\Application;

use Ds\Map;
use PCF\Addendum\Command\CommandScanner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\LazyCommand;

final class ConsoleApplicationFactory
{
    /** @param iterable<string> $commandPaths */
    public function create(string $name, string $version, iterable $commandPaths): Application
    {
        $console = new Application($name, $version);
        $commands = new Map();

        foreach ($commandPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            foreach (new CommandScanner($path)->scanCommands() as $commandName => $definition) {
                $commands->put($commandName, $definition);
            }
        }

        foreach ($commands as $definition) {
            $factory = $definition->factory;
            $class = $definition->class;
            $console->add(new LazyCommand(
                name: $definition->name,
                aliases: [],
                description: $definition->description,
                isHidden: false,
                commandFactory: $factory !== null ? fn() => new $factory()->create() : fn() => new $class(),
            ));
        }

        return $console;
    }
}
