<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class HttpCacheRuntime
{
    public function __construct(
        public HttpCacheConfigurationInterface $configuration,
        public HttpCacheBackendProvider $backendProvider
    ) {
    }
}
