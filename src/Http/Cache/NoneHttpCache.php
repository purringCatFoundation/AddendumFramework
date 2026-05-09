<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class NoneHttpCache implements HttpCacheConfigurationInterface
{
    public function __construct(
        public HttpCacheContext $context
    ) {
    }
}
