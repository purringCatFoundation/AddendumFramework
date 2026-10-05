<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use RuntimeException;

final readonly class HttpCacheBackendProviderFactory
{
    public function create(HttpCacheConfigurationInterface $configuration): HttpCacheBackendProvider
    {
        if ($configuration instanceof RedisHttpCache) {
            return new RedisHttpCacheBackendProviderFactory()->create($configuration);
        }

        $providers = [
            new NoneHttpCacheBackendProvider(),
            new VarnishHttpCacheBackendProvider(),
            new NginxHttpCacheBackendProvider(),
            new CaddyHttpCacheBackendProvider(),
            new CloudflareHttpCacheBackendProvider(),
        ];
        foreach ($providers as $provider) {
            if ($provider->supports($configuration)) {
                return $provider;
            }
        }

        throw new RuntimeException(sprintf('No HTTP cache backend provider supports %s', $configuration::class));
    }
}
