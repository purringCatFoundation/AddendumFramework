<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use DateInterval;
use PCF\Addendum\Auth\ApplicationTokenHashCache;
use PCF\Addendum\Auth\ApplicationTokenValidator;
use PCF\Addendum\Repository\User\ApplicationTokenRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class ApplicationTokenValidatorTest extends TestCase
{
    public function testCacheMissLoadsHashFromDatabaseAndCachesIt(): void
    {
        $token = 'application-token';
        $jti = 'jti-1';
        $cache = new ApplicationTokenValidatorTestCache();
        $repository = $this->repository([$jti => hash('sha256', $token)]);
        $validator = new ApplicationTokenValidator($repository, new ApplicationTokenHashCache($cache));

        self::assertTrue($validator->isKnown($jti, $token));
        self::assertSame(hash('sha256', $token), $cache->get('auth:application_token:jti-1'));
        self::assertSame(300, $cache->ttl['auth:application_token:jti-1']);
    }

    public function testCacheHitUsesHashEqualsAgainstCachedHash(): void
    {
        $cache = new ApplicationTokenValidatorTestCache();
        $cache->set('auth:application_token:jti-1', hash('sha256', 'known-token'), 300);
        $validator = new ApplicationTokenValidator($this->repository([]), new ApplicationTokenHashCache($cache));

        self::assertTrue($validator->isKnown('jti-1', 'known-token'));
        self::assertFalse($validator->isKnown('jti-1', 'different-token'));
    }

    public function testCacheMissWithoutDatabaseHashRejectsToken(): void
    {
        $validator = new ApplicationTokenValidator($this->repository([]), new ApplicationTokenHashCache(new ApplicationTokenValidatorTestCache()));

        self::assertFalse($validator->isKnown('missing-jti', 'application-token'));
    }

    /**
     * @param array<string, string> $hashesByJti
     */
    private function repository(array $hashesByJti): ApplicationTokenRepository
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE application_tokens (jti VARCHAR(100) PRIMARY KEY, token_hash VARCHAR(255) NOT NULL)');

        foreach ($hashesByJti as $jti => $hash) {
            $stmt = $pdo->prepare('INSERT INTO application_tokens (jti, token_hash) VALUES (:jti, :token_hash)');
            $stmt->execute(['jti' => $jti, 'token_hash' => $hash]);
        }

        return new ApplicationTokenRepository($pdo);
    }
}

final class ApplicationTokenValidatorTestCache implements CacheInterface
{
    /** @var array<string, mixed> */
    public array $values = [];

    /** @var array<string, int|null> */
    public array $ttl = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->ttl[$key] = is_int($ttl) ? $ttl : null;

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
            yield $key => $this->get($key, $default);
        }
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}
