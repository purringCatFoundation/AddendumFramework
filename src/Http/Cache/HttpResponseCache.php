<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Ds\Vector;
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

    /** @param iterable<string> $resources */
    public function set(string $key, ResponseInterface $response, int $ttl, iterable $resources): void
    {
        try {
            $this->cache->set($key, HttpCachedResponse::fromResponse($response)->toJson(), $ttl);
            $this->recordResources($key, $resources);
        } catch (Throwable) {
        }
    }

    /** @param iterable<string> $resources */
    public function invalidate(iterable $resources): void
    {
        try {
            foreach ($resources as $resource) {
                $indexKey = $this->resourceKey($resource);
                $keys = $this->resourceCacheKeys($indexKey);

                if (!$keys->isEmpty()) {
                    $this->cache->deleteMultiple($keys);
                }

                $this->cache->delete($indexKey);
            }
        } catch (Throwable) {
        }
    }

    /** @param iterable<string> $resources */
    private function recordResources(string $key, iterable $resources): void
    {
        foreach ($resources as $resource) {
            $indexKey = $this->resourceKey($resource);
            $keys = $this->resourceCacheKeys($indexKey);

            if (!$keys->contains($key)) {
                $keys->push($key);
            }

            $this->cache->set($indexKey, json_encode($keys->toArray(), JSON_THROW_ON_ERROR));
        }
    }

    /** @return Vector<string> */
    private function resourceCacheKeys(string $indexKey): Vector
    {
        $payload = $this->cache->get($indexKey);

        if (!is_string($payload)) {
            return new Vector();
        }

        try {
            $keys = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return new Vector();
        }

        if (!is_array($keys)) {
            return new Vector();
        }

        return new Vector(array_values(array_filter($keys, is_string(...))));
    }

    private function resourceKey(string $resource): string
    {
        return $this->keyPrefix . 'resource:' . hash('sha256', $resource);
    }
}
