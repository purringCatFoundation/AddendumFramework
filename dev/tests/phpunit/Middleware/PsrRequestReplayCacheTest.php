<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use DateInterval;
use PCF\Addendum\Http\Middleware\PsrRequestReplayCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class PsrRequestReplayCacheTest extends TestCase
{
    public function testDelegatesReplayStateToPsrCache(): void
    {
        $cache = new PsrReplayCacheTestCache();
        $replayCache = new PsrRequestReplayCache($cache);

        self::assertTrue($replayCache->requiresNonce());
        self::assertFalse($replayCache->has('request-key'));

        $replayCache->set('request-key', '1', 300);

        self::assertTrue($replayCache->has('request-key'));
        self::assertSame('1', $cache->values['request-key']);
        self::assertSame(300, $cache->ttl['request-key']);
    }
}

final class PsrReplayCacheTestCache implements CacheInterface
{
    /** @var array<string, mixed> */
    public array $values = [];
    /** @var array<string, int|DateInterval|null> */
    public array $ttl = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->ttl[$key] = $ttl;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key], $this->ttl[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->ttl = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get((string) $key, $default);
        }
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}
