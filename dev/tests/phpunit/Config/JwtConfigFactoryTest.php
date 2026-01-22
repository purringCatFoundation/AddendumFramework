<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Config;

use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use InvalidArgumentException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class JwtConfigFactoryTest extends TestCase
{
    public function testCreateWithValidEnvironment(): void
    {
        $mockEnvProvider = $this->createMock(SystemEnvironmentProvider::class);
        $mockEnvProvider
            ->method('get')
            ->willReturnMap([
                ['JWT_SECRET', null, 'test-secret-from-env-32-bytes-long'],
                ['JWT_ACCESS_TOKEN_LIFETIME', '7200', '7200'],
                ['JWT_REFRESH_TOKEN_LIFETIME', '1209600', '1209600']
            ]);

        $factory = new JwtConfigFactory($mockEnvProvider);
        $config = $factory->create();

        $this->assertInstanceOf(JwtConfig::class, $config);
        $this->assertSame('test-secret-from-env-32-bytes-long', $config->secret);
        $this->assertSame(7200, $config->accessTokenLifetime);
        $this->assertSame(1209600, $config->refreshTokenLifetime);
    }

    public function testCreateWithDefaults(): void
    {
        $mockEnvProvider = $this->createMock(SystemEnvironmentProvider::class);
        $mockEnvProvider
            ->method('get')
            ->willReturnMap([
                ['JWT_SECRET', null, 'test-secret-with-defaults-32-bytes'],
                ['JWT_ACCESS_TOKEN_LIFETIME', '7200', '7200'],
                ['JWT_REFRESH_TOKEN_LIFETIME', '1209600', '1209600']
            ]);

        $factory = new JwtConfigFactory($mockEnvProvider);
        $config = $factory->create();

        $this->assertInstanceOf(JwtConfig::class, $config);
        $this->assertSame('test-secret-with-defaults-32-bytes', $config->secret);
        $this->assertSame(7200, $config->accessTokenLifetime);    // Default
        $this->assertSame(1209600, $config->refreshTokenLifetime); // Default
    }

    public function testCreateThrowsExceptionForMissingSecret(): void
    {
        $mockEnvProvider = $this->createMock(SystemEnvironmentProvider::class);
        $mockEnvProvider
            ->method('get')
            ->with('JWT_SECRET')
            ->willThrowException(new RuntimeException('Environment variable JWT_SECRET is required but not set'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable JWT_SECRET is required but not set');

        $factory = new JwtConfigFactory($mockEnvProvider);
        $factory->create();
    }

    public function testCreateWithInvalidValues(): void
    {
        $mockEnvProvider = $this->createMock(SystemEnvironmentProvider::class);
        $mockEnvProvider
            ->method('get')
            ->willReturnMap([
                ['JWT_SECRET', null, 'test-secret-32-bytes-long-test'],
                ['JWT_ACCESS_TOKEN_LIFETIME', '7200', '30'], // Too short
                ['JWT_REFRESH_TOKEN_LIFETIME', '1209600', '1209600']
            ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token lifetime must be at least 60 seconds');

        $factory = new JwtConfigFactory($mockEnvProvider);
        $factory->create();
    }
}