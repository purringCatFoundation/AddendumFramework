<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class RedisHttpCache implements HttpCacheConfigurationInterface
{
    public function __construct(
        public HttpCacheContext $context,
        public ?string $url = null,
        public string $host = '127.0.0.1',
        public int $port = 6379,
        public ?string $password = null,
        public int $database = 1,
        public string $keyPrefix = 'addendum:http_cache:',
        public string $hitHeader = 'X-Redis-Cache'
    ) {
    }
}
