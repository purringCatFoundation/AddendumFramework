<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cache;

use Predis\Client;

class RedisCacheFactory
{
    public function create(): RedisCache
    {
        $url = getenv('REDIS_URL');
        $client = new Client($url);
        return new RedisCache($client);
    }
}
