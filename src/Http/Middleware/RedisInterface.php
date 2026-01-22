<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

/**
 * Interface for Redis operations used by rate limiting
 *
 * This interface allows for easier testing by not depending on the concrete Predis\Client class.
 */
interface RedisInterface
{
    public function zremrangebyscore(string $key, string $min, string $max): int;

    public function zcard(string $key): int;

    public function zadd(string $key, array $values): int;

    public function expire(string $key, int $seconds): bool;
}
