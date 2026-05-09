<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class NginxHttpCache implements HttpCacheConfigurationInterface
{
    public function __construct(
        public HttpCacheContext $context,
        public string $tagHeader = 'X-Cache-Tags',
        public string $accelExpiresHeader = 'X-Accel-Expires',
        public bool $emitAccelExpires = true,
        public string $purgeUrl = '',
        public string $purgeMethod = 'PURGE'
    ) {
    }
}
