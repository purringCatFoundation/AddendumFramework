<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class HttpCacheRuntimeFactory
{
    public function __construct(
        private HttpCacheConfigurationFactory $configurationFactory,
        private HttpCacheBackendProviderFactory $backendProviderFactory
    ) {
    }

    public function create(): HttpCacheRuntime
    {
        $configuration = $this->configurationFactory->create();

        return new HttpCacheRuntime(
            configuration: $configuration,
            backendProvider: $this->backendProviderFactory->create($configuration)
        );
    }
}
