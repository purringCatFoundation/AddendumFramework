<?php
declare(strict_types=1);

namespace PCF\Addendum\Dev;

use PCF\Addendum\Application\Application;

final class DevApp extends Application
{
    public function getActionPaths(): array
    {
        $rootDir = $this->getProjectDir();

        return [
            $rootDir . '/src/Action',
            __DIR__ . '/Action',
        ];
    }

    protected function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
