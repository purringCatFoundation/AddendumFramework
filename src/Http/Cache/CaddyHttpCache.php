<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class CaddyHttpCache implements HttpCacheConfigurationInterface
{
    public const string STANDARD = 'standard';
    public const string SOUIN = 'souin';

    public function __construct(
        public HttpCacheContext $context,
        public string $cacheHandler = self::STANDARD,
        public string $tagHeader = 'X-Cache-Tags',
        public string $souinTagHeader = 'Souin-Cache-Tags'
    ) {
    }
}
