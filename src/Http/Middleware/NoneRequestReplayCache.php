<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

final readonly class NoneRequestReplayCache implements RequestReplayCache
{
    public function requiresNonce(): bool
    {
        return false;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function set(string $key, string $value, int $ttl): void
    {
    }
}
