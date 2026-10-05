<?php
declare(strict_types=1);

namespace PCF\Addendum\Dev;

use Ds\Vector;
use PCF\Addendum\Application\Application;

final class DevApp extends Application
{
    /** @return Vector<string> */
    public function getActionPaths(): Vector
    {
        $rootDir = $this->getProjectDir();

        return new Vector([
            $rootDir . '/src/Action',
            __DIR__ . '/Action',
        ]);
    }

    protected function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
