<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class RedisHttpCache implements HttpCacheConfigurationInterface
{
    public HttpCacheContext $context;

    public function __construct(
        ?HttpCacheContext $context = null,
        public ?string $url = null,
        public string $host = '127.0.0.1',
        public int $port = 6379,
        public ?string $password = null,
        public int $database = 1,
        public string $keyPrefix = 'addendum:http_cache:',
        public string $hitHeader = 'X-Redis-Cache'
    ) {
        $this->context = $context ?? new HttpCacheContext();
    }
}
