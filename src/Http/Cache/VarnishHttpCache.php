<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class VarnishHttpCache implements HttpCacheConfigurationInterface
{
    public HttpCacheContext $context;

    public function __construct(
        ?HttpCacheContext $context = null,
        public string $tagHeader = 'Surrogate-Key',
        public string $surrogateControlHeader = 'Surrogate-Control',
        public string $purgeUrl = '',
        public string $purgeMethod = 'PURGE'
    ) {
        $this->context = $context ?? new HttpCacheContext();
    }
}
