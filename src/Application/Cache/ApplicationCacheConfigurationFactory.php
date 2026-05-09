<?php
declare(strict_types=1);

namespace PCF\Addendum\Application\Cache;

use PCF\Addendum\Config\SystemEnvironmentProvider;

final readonly class ApplicationCacheConfigurationFactory
{
    public function __construct(
        private SystemEnvironmentProvider $environmentProvider
    ) {
    }

    public function create(?string $baseDirectory = null): ApplicationCacheConfiguration
    {
        $environment = strtolower(trim($this->environmentProvider->get('APP_ENV', 'prod')));
        $mode = ApplicationCacheMode::tryFrom(strtolower(trim($this->environmentProvider->get('APP_CACHE', 'auto'))))
            ?? ApplicationCacheMode::AUTO;
        $directory = trim($this->environmentProvider->get('APP_CACHE_DIR', 'var/cache/addendum/compiled'));

        if (!$this->isAbsolutePath($directory)) {
            $directory = rtrim($baseDirectory ?? getcwd(), '/') . '/' . $directory;
        }

        return new ApplicationCacheConfiguration(
            mode: $mode,
            environment: $environment,
            compiledDirectory: rtrim($directory, '/')
        );
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('#^[A-Za-z]:[\\/]#', $path) === 1;
    }
}
