<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Http\Routing;

use megachriz\classtools\Iterator\ClassIterator;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class ActionScanner
{
    public function __construct(
        private readonly string $actionDirectory
    ) {
    }

    /**
     * Scans and returns all action classes
     *
     * @return list<ReflectionClass>
     */
    public function scanActions(): array
    {
        $directory = $this->actionDirectory;

        // Create Symfony Finder for Action files
        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->name('*Action.php')
            ->notName('*Factory.php');

        // Use ClassIterator to extract class names from files
        $iterator = new ClassIterator($finder);

        $actions = [];

        /** @var ReflectionClass $reflection */
        foreach ($iterator as $className => $reflection) {
            // Skip abstracts, interfaces, traits
            if (!$reflection->isInstantiable()) {
                continue;
            }

            $actions[] = $reflection;
        }

        return $actions;
    }
}
