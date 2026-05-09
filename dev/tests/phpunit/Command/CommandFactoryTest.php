<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use PCF\Addendum\Command\CacheCleanupCommand;
use PCF\Addendum\Command\CacheCleanupCommandFactory;
use PCF\Addendum\Command\CacheDebugCommand;
use PCF\Addendum\Command\CacheDebugCommandFactory;
use PCF\Addendum\Command\CacheWarmupCommand;
use PCF\Addendum\Command\CacheWarmupCommandFactory;
use PCF\Addendum\Command\ClearHttpCacheCommand;
use PCF\Addendum\Command\ClearHttpCacheCommandFactory;
use PCF\Addendum\Command\HelloCommand;
use PCF\Addendum\Command\HelloCommandFactory;
use PHPUnit\Framework\TestCase;

final class CommandFactoryTest extends TestCase
{
    public function testCreatesSimpleCommands(): void
    {
        self::assertInstanceOf(HelloCommand::class, new HelloCommandFactory()->create());
        self::assertInstanceOf(CacheWarmupCommand::class, new CacheWarmupCommandFactory()->create());
        self::assertInstanceOf(CacheCleanupCommand::class, new CacheCleanupCommandFactory()->create());
        self::assertInstanceOf(CacheDebugCommand::class, new CacheDebugCommandFactory()->create());
    }

    public function testCreatesHttpCacheClearCommandFromEnvironment(): void
    {
        $_ENV['HTTP_CACHE_URL'] = 'data://text/plain,ok';

        try {
            self::assertInstanceOf(ClearHttpCacheCommand::class, new ClearHttpCacheCommandFactory()->create());
        } finally {
            unset($_ENV['HTTP_CACHE_URL']);
        }
    }
}
