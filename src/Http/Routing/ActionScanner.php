<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use Ds\Vector;
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
     * @return Vector<ReflectionClass>
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
        $tokens = token_get_all($contents);

        $namespace = '';
        $class = '';
        $namespaceFound = false;
        $classFound = false;

        foreach ($tokens as $i => $token) {
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
