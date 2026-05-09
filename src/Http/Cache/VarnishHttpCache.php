<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class VarnishHttpCache implements HttpCacheConfigurationInterface
{
    public function __construct(
        public HttpCacheContext $context,
        public string $tagHeader = 'Surrogate-Key',
        public string $surrogateControlHeader = 'Surrogate-Control',
        public string $purgeUrl = '',
        public string $purgeMethod = 'PURGE'
    ) {
    }
}
