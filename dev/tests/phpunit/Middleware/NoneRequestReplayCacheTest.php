<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Middleware;

use PCF\Addendum\Http\Middleware\NoneRequestReplayCache;
use PHPUnit\Framework\TestCase;

final class NoneRequestReplayCacheTest extends TestCase
{
    public function testNoopReplayCacheNeverRequiresOrStoresNonce(): void
    {
        $cache = new NoneRequestReplayCache();

        self::assertFalse($cache->requiresNonce());
        self::assertFalse($cache->has('request-key'));

        $cache->set('request-key', 'nonce', 300);

        self::assertFalse($cache->has('request-key'));
    }
}
