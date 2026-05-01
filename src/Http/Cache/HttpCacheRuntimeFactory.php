<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class HttpCacheRuntimeFactory
{
    public function __construct(
        private HttpCacheConfigurationFactory $configurationFactory = new HttpCacheConfigurationFactory(),
        private HttpCacheBackendProviderFactory $backendProviderFactory = new HttpCacheBackendProviderFactory()
    ) {
    }

    public function create(): ?HttpCacheRuntime
    {
        $configuration = $this->configurationFactory->create();

        if ($configuration === null) {
            return null;
        }

        return new HttpCacheRuntime(
            configuration: $configuration,
            backendProvider: $this->backendProviderFactory->create($configuration)
        );
    }
}
