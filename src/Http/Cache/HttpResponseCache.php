<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

final readonly class HttpResponseCache
{
    public function __construct(
        private CacheInterface $cache,
        private string $keyPrefix = 'http_cache:'
    ) {
    }

    public function get(string $key): ?ResponseInterface
    {
        try {
            $payload = $this->cache->get($key);
        } catch (Throwable) {
            return null;
        }

        if (!is_string($payload)) {
            return null;
        }

        return HttpCachedResponse::fromJson($payload)?->toResponse();
    }

    /**
     * @param list<string> $resources
     */
    public function set(string $key, ResponseInterface $response, int $ttl, array $resources): void
    {
        try {
            $this->cache->set($key, HttpCachedResponse::fromResponse($response)->toJson(), $ttl);
            $this->recordResources($key, $resources);
        } catch (Throwable) {
        }
    }

    /**
     * @param list<string> $resources
     */
    public function invalidate(array $resources): void
    {
        try {
            foreach ($resources as $resource) {
                $indexKey = $this->resourceKey($resource);
                $keys = $this->resourceCacheKeys($indexKey);

                if ($keys !== []) {
                    $this->cache->deleteMultiple($keys);
                }

                $this->cache->delete($indexKey);
            }
        } catch (Throwable) {
        }
    }

    /**
     * @param list<string> $resources
     */
    private function recordResources(string $key, array $resources): void
    {
        foreach ($resources as $resource) {
            $indexKey = $this->resourceKey($resource);
            $keys = $this->resourceCacheKeys($indexKey);
            $keys[] = $key;
            $keys = array_values(array_unique($keys));

            $this->cache->set($indexKey, json_encode($keys, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * @return list<string>
     */
    private function resourceCacheKeys(string $indexKey): array
    {
        $payload = $this->cache->get($indexKey);

        if (!is_string($payload)) {
            return [];
        }

        try {
            $keys = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        if (!is_array($keys)) {
            return [];
        }

        return array_values(array_filter($keys, is_string(...)));
    }

    private function resourceKey(string $resource): string
    {
        return $this->keyPrefix . 'resource:' . hash('sha256', $resource);
    }
}
