<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class CaddyHttpCache implements HttpCacheConfigurationInterface
{
    public const string STANDARD = 'standard';
    public const string SOUIN = 'souin';

    public HttpCacheContext $context;

    public function __construct(
        ?HttpCacheContext $context = null,
        public string $cacheHandler = self::STANDARD,
        public string $tagHeader = 'X-Cache-Tags',
        public string $souinTagHeader = 'Souin-Cache-Tags'
    ) {
        $this->context = $context ?? new HttpCacheContext();
    }
}
