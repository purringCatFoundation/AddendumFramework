<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use PCF\Addendum\Cache\RedisCache;
use Predis\Client;

final class RedisHttpCacheBackendProviderFactory
{
    public function create(RedisHttpCache $configuration): RedisHttpCacheBackendProvider
    {
        return new RedisHttpCacheBackendProvider(
            $this->redisCache($configuration),
            new HttpCacheKeyGenerator()
        );
    }

    private function redisCache(RedisHttpCache $configuration): HttpResponseCache
    {
        if ($configuration->url !== null) {
            $client = new Client($configuration->url);
        } else {
            $parameters = [
                'host' => $configuration->host,
                'port' => $configuration->port,
                'database' => $configuration->database,
            ];
            if ($configuration->password !== null) {
                $parameters['password'] = $configuration->password;
            }
            $client = new Client($parameters);
        }

        return new HttpResponseCache(new RedisCache($client), $configuration->keyPrefix);
    }
}
