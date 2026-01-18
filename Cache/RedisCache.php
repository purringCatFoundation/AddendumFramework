<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cache;

use Predis\Client;
use Psr\SimpleCache\CacheInterface;
use DateInterval;

class RedisCache implements CacheInterface
{
    public function __construct(private Client $client)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->client->get($key);
        return $value !== null ? $value : $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $seconds = $this->ttlToSeconds($ttl);
        if ($seconds !== null) {
            $this->client->setex($key, $seconds, (string) $value);
        } else {
            $this->client->set($key, (string) $value);
        }
        return true;
    }

    public function delete(string $key): bool
    {
        $this->client->del([$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->client->flushdb();
        return true;
    }

    public function getMultiple($keys, mixed $default = null): iterable
    {
        $values = [];
        $results = $this->client->mget($keys);
        foreach ($keys as $i => $key) {
            $values[$key] = $results[$i] !== null ? $results[$i] : $default;
        }
        return $values;
    }

    public function setMultiple($values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        $this->client->del($keys);
        return true;
    }

    public function has(string $key): bool
    {
        return (bool) $this->client->exists($key);
    }

    private function ttlToSeconds(null|int|DateInterval $ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            $now = new \DateTimeImmutable();
            return $now->add($ttl)->getTimestamp() - $now->getTimestamp();
        }
        return $ttl;
    }
}
