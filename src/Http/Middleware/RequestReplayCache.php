<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

interface RequestReplayCache
{
    public function requiresNonce(): bool;

    public function has(string $key): bool;

    public function set(string $key, string $value, int $ttl): void;
}
