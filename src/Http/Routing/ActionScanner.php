<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use Ds\Vector;
use PCF\Addendum\Util\ClassNameReader;
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
     * @return Vector<ReflectionClass<object>>
     */
    public function scanActions(): Vector
    {
        if (!is_dir($this->actionDirectory)) {
            return new Vector();
        }

        $finder = new Finder();
        $finder->files()
            ->in($this->actionDirectory)
            ->name('*Action.php')
            ->notName('*Factory.php');

        $actions = new Vector();

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

            $actions->push($reflection);
        }

        return $actions;
    }

    /**
     * Extract fully qualified class name from PHP file
     */
    private function extractClassName(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        return $contents === false ? null : ClassNameReader::fromSource($contents);
    }
}
