<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use PCF\Addendum\Cache\RedisCache;
use Predis\Client;
use RuntimeException;

final readonly class HttpCacheBackendProviderFactory
{
    public function create(HttpCacheConfigurationInterface $configuration): HttpCacheBackendProvider
    {
        return match (true) {
            $configuration instanceof NoneHttpCache => new NoneHttpCacheBackendProvider(),
            $configuration instanceof RedisHttpCache => new RedisHttpCacheBackendProvider(
                $this->redisCache($configuration),
                new HttpCacheKeyGenerator()
            ),
            $configuration instanceof VarnishHttpCache => new VarnishHttpCacheBackendProvider(),
            $configuration instanceof NginxHttpCache => new NginxHttpCacheBackendProvider(),
            $configuration instanceof CaddyHttpCache => new CaddyHttpCacheBackendProvider(),
            $configuration instanceof CloudflareHttpCache => new CloudflareHttpCacheBackendProvider(),
            default => throw new RuntimeException(sprintf('No HTTP cache backend provider supports %s', $configuration::class)),
        };
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
