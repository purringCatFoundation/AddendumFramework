<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class CloudflareHttpCache implements HttpCacheConfigurationInterface
{
    public HttpCacheContext $context;

    public function __construct(
        ?HttpCacheContext $context = null,
        public string $zoneId = '',
        public string $apiToken = '',
        public string $tagHeader = 'Cache-Tag',
        public string $cdnCacheControlHeader = 'CDN-Cache-Control',
        public string $cloudflareCacheControlHeader = 'Cloudflare-CDN-Cache-Control',
        public bool $purgeByTags = false
    ) {
        $this->context = $context ?? new HttpCacheContext();
    }
}
