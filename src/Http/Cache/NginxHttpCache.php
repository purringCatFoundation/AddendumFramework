<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class NginxHttpCache implements HttpCacheConfigurationInterface
{
    public HttpCacheContext $context;

    public function __construct(
        ?HttpCacheContext $context = null,
        public string $tagHeader = 'X-Cache-Tags',
        public string $accelExpiresHeader = 'X-Accel-Expires',
        public bool $emitAccelExpires = true,
        public string $purgeUrl = '',
        public string $purgeMethod = 'PURGE'
    ) {
        $this->context = $context ?? new HttpCacheContext();
    }
}
