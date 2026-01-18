<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Command;

use megachriz\classtools\Iterator\ClassIterator;
use Pradzikowski\Framework\Attribute\Command;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * CommandScanner - Scans directories for Command classes with #[Command] attribute
 */
class CommandScanner
{
    public function __construct(
        private readonly string $commandDirectory
    ) {
    }

    /**
     * Scan directory for Command classes with #[Command] attribute
     *
     * @return array<string, array{name: string, description: string, factory: string}> Map of command name => definition
     */
    public function scanCommands(): array
    {
        $commands = [];

        if (!is_dir($this->commandDirectory)) {
            return $commands;
        }

        // Create Symfony Finder for Command files
        $finder = new Finder();
        $finder->files()->name('*Command.php')->in($this->commandDirectory);

        // Use ClassIterator to extract class names from files
        $iterator = new ClassIterator($finder);

        /** @var ReflectionClass $reflection */
        foreach ($iterator as $className => $reflection) {
            // Skip non-Command classes (interfaces, traits, abstract classes)
            if (!$reflection->isInstantiable()) {
                continue;
            }

            // Get Command attribute
            $attributes = $reflection->getAttributes(Command::class);
            if (empty($attributes)) {
                continue;
            }

            /** @var Command $commandAttr */
            $commandAttr = $attributes[0]->newInstance();

            // Find corresponding factory class
            $factoryClassName = $className . 'Factory';
            if (!class_exists($factoryClassName)) {
                continue;
            }

            $commands[$commandAttr->name] = [
                'name' => $commandAttr->name,
                'description' => $commandAttr->description,
                'factory' => $factoryClassName,
            ];
        }

        return $commands;
    }
}
