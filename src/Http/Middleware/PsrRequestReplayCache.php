<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use Psr\SimpleCache\CacheInterface;

final readonly class PsrRequestReplayCache implements RequestReplayCache
{
    public function __construct(
        private CacheInterface $cache
    ) {
    }

    public function requiresNonce(): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function set(string $key, string $value, int $ttl): void
    {
        $this->cache->set($key, $value, $ttl);
    }
}
