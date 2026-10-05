<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use Psr\SimpleCache\CacheInterface;

class ApplicationTokenHashCache
{
    private const int TTL_SECONDS = 300;
    private const string KEY_PREFIX = 'auth:application_token:';

    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public function get(string $jti): ?string
    {
        $hash = $this->cache->get($this->key($jti));

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    public function remember(string $jti, string $tokenHash): void
    {
        $this->cache->set($this->key($jti), $tokenHash, self::TTL_SECONDS);
    }

    private function key(string $jti): string
    {
        return self::KEY_PREFIX . $jti;
    }
}
