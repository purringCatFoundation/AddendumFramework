<?php
declare(strict_types=1);

namespace PCF\Addendum\Application\Cache;

final readonly class ApplicationCacheConfiguration
{
    public function __construct(
        public ApplicationCacheMode $mode,
        public string $environment,
        public string $compiledDirectory
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->mode !== ApplicationCacheMode::OFF;
    }

    public function shouldRefreshOnRequest(): bool
    {
        return $this->isEnabled() && $this->environment === 'dev';
    }

    public function routesFile(): string
    {
        return $this->compiledDirectory . '/routes.php';
    }

    public function metadataFile(): string
    {
        return $this->compiledDirectory . '/metadata.php';
    }

    public function appFile(): string
    {
        return $this->compiledDirectory . '/app.php';
    }
}
