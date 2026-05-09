<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use Ds\Map;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;

/**
 * CommandScanner - Scans directories for Command classes with #[AsCommand] attribute
 */
class CommandScanner
{
    public function __construct(
        private readonly string $commandDirectory
    ) {
    }

    /**
     * Scan directory for Command classes with #[AsCommand] attribute
     *
     * @return Map<string, CommandDefinition>
     */
    public function scanCommands(): Map
    {
        $commands = new Map();

        if (!is_dir($this->commandDirectory)) {
            return $commands;
        }

        $finder = new Finder();
        $finder->files()
            ->name('*Command.php')
            ->notName('*Factory.php')
            ->in($this->commandDirectory);

        foreach ($finder as $file) {
            $className = $this->extractClassName($file->getRealPath());

            if ($className === null) {
                continue;
            }

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if (!$reflection->isInstantiable()) {
                continue;
            }

            if (!$reflection->isSubclassOf(Command::class)) {
                continue;
            }

            $attributes = $reflection->getAttributes(AsCommand::class);
            if (empty($attributes)) {
                continue;
            }

            /** @var AsCommand $commandAttr */
            $commandAttr = $attributes[0]->newInstance();

            $factoryClassName = $className . 'Factory';
            $hasFactory = class_exists($factoryClassName);

            $commands->put($commandAttr->name, new CommandDefinition(
                name: $commandAttr->name,
                description: $commandAttr->description ?? '',
                class: $className,
                factory: $hasFactory ? $factoryClassName : null,
            ));
        }

        return $commands;
    }

    /**
     * Extract fully qualified class name from PHP file
     */
    private function extractClassName(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        $tokens = token_get_all($contents);

        $namespace = '';
        $class = '';
        $namespaceFound = false;
        $classFound = false;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespaceFound = true;
                continue;
            }

            if ($namespaceFound && $token[0] === T_NAME_QUALIFIED) {
                $namespace = $token[1];
                $namespaceFound = false;
                continue;
            }

            if ($namespaceFound && $token[0] === T_STRING) {
                $namespace .= $token[1];
                continue;
            }

            if ($namespaceFound && $token[0] === T_NS_SEPARATOR) {
                $namespace .= '\\';
                continue;
            }

            if ($token[0] === T_CLASS) {
                $classFound = true;
                continue;
            }

            if ($classFound && $token[0] === T_STRING) {
                $class = $token[1];
                break;
            }
        }

        if ($class === '') {
            return null;
        }

        return $namespace !== '' ? $namespace . '\\' . $class : $class;
    }
}
