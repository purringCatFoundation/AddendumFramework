<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class CloudflareHttpCache implements HttpCacheConfigurationInterface
{
    public function __construct(
        public HttpCacheContext $context,
        public string $zoneId = '',
        public string $apiToken = '',
        public string $tagHeader = 'Cache-Tag',
        public string $cdnCacheControlHeader = 'CDN-Cache-Control',
        public string $cloudflareCacheControlHeader = 'Cloudflare-CDN-Cache-Control',
        public bool $purgeByTags = false
    ) {
    }
}
