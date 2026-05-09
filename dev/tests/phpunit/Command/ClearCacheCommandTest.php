<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Command;

use DateInterval;
use PCF\Addendum\Command\ClearCacheCommand;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearCacheCommandTest extends TestCase
{
    public function testClearsApplicationCache(): void
    {
        $cache = new ClearCacheCommandTestCache();
        $tester = new CommandTester(new ClearCacheCommand($cache));

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertTrue($cache->cleared);
        self::assertStringContainsString('Cache cleared.', $tester->getDisplay());
    }
}

final class ClearCacheCommandTestCache implements CacheInterface
{
    public bool $cleared = false;

    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        $this->cleared = true;

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $default;
        }
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }
}
