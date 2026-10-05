<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Config;

use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use InvalidArgumentException;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class JwtConfigFactoryTest extends TestCase
{
    public function testCreateWithValidEnvironment(): void
    {
        $keyPair = JwtKeyPair::shared();
        $mockEnvProvider = $this->createMock(SystemEnvironmentProvider::class);
        $mockEnvProvider
            ->method('get')
            ->willReturnMap([
                ['JWT_PRIVATE_KEY_PATH', null, $keyPair->privateKeyPath],
                ['JWT_PUBLIC_KEY_PATH', null, $keyPair->publicKeyPath],
                ['JWT_PRIVATE_KEY_PASSPHRASE', '', 'test-passphrase'],
                ['JWT_ACCESS_TOKEN_LIFETIME', '7200', '7200'],
                ['JWT_REFRESH_TOKEN_LIFETIME', '1209600', '1209600']
            ]);

        $factory = new JwtConfigFactory($mockEnvProvider);
        $config = $factory->create();

        $this->assertInstanceOf(JwtConfig::class, $config);
        $this->assertSame($keyPair->privateKeyPath, $config->privateKeyPath);
        $this->assertSame($keyPair->publicKeyPath, $config->publicKeyPath);
        $this->assertSame('test-passphrase', $config->privateKeyPassphrase);
        $this->assertSame(7200, $config->accessTokenLifetime);
        $this->assertSame(1209600, $config->refreshTokenLifetime);
    }

    public function testCreateWithDefaults(): void
    {
        $keyPair = JwtKeyPair::shared();
        $mockEnvProvider = $this->createMock(SystemEnvironmentProvider::class);
        $mockEnvProvider
            ->method('get')
            ->willReturnMap([
                ['JWT_PRIVATE_KEY_PATH', null, $keyPair->privateKeyPath],
                ['JWT_PUBLIC_KEY_PATH', null, $keyPair->publicKeyPath],
                ['JWT_PRIVATE_KEY_PASSPHRASE', '', ''],
                ['JWT_ACCESS_TOKEN_LIFETIME', '7200', '7200'],
                ['JWT_REFRESH_TOKEN_LIFETIME', '1209600', '1209600']
            ]);

        $factory = new JwtConfigFactory($mockEnvProvider);
        $config = $factory->create();

        $this->assertInstanceOf(JwtConfig::class, $config);
        $this->assertSame($keyPair->privateKeyPath, $config->privateKeyPath);
        $this->assertSame($keyPair->publicKeyPath, $config->publicKeyPath);
        $this->assertNull($config->privateKeyPassphrase);
        $this->assertSame(7200, $config->accessTokenLifetime);    // Default
        $this->assertSame(1209600, $config->refreshTokenLifetime); // Default
    }

    public function testCreateThrowsExceptionForMissingPrivateKeyPath(): void
    {
        $mockEnvProvider = $this->createMock(SystemEnvironmentProvider::class);
        $mockEnvProvider
            ->method('get')
            ->with('JWT_PRIVATE_KEY_PATH')
            ->willThrowException(new RuntimeException('Environment variable JWT_PRIVATE_KEY_PATH is required but not set'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable JWT_PRIVATE_KEY_PATH is required but not set');

        $factory = new JwtConfigFactory($mockEnvProvider);
        $factory->create();
    }

    public function testCreateWithInvalidValues(): void
    {
        $keyPair = JwtKeyPair::shared();
        $mockEnvProvider = $this->createMock(SystemEnvironmentProvider::class);
        $mockEnvProvider
            ->method('get')
            ->willReturnMap([
                ['JWT_PRIVATE_KEY_PATH', null, $keyPair->privateKeyPath],
                ['JWT_PUBLIC_KEY_PATH', null, $keyPair->publicKeyPath],
                ['JWT_PRIVATE_KEY_PASSPHRASE', '', ''],
                ['JWT_ACCESS_TOKEN_LIFETIME', '7200', '30'], // Too short
                ['JWT_REFRESH_TOKEN_LIFETIME', '1209600', '1209600']
            ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token lifetime must be at least 60 seconds');

        $factory = new JwtConfigFactory($mockEnvProvider);
        $factory->create();
    }
}
