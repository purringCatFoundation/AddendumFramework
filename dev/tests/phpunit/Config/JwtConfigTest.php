<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Config;

use PCF\Addendum\Config\JwtConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class JwtConfigTest extends TestCase
{
    public function testConstructorWithValidValues(): void
    {
        $config = new JwtConfig('test-secret-32-bytes-long-test', 7200, 1209600);
        
        $this->assertSame('test-secret-32-bytes-long-test', $config->secret);
        $this->assertSame(7200, $config->accessTokenLifetime);
        $this->assertSame(1209600, $config->refreshTokenLifetime);
    }
    
    public function testConstructorThrowsExceptionForEmptySecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT secret cannot be empty');
        
        new JwtConfig('', 3600, 86400);
    }
    
    public function testConstructorThrowsExceptionForShortAccessTokenLifetime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token lifetime must be at least 60 seconds');
        
        new JwtConfig('test-secret-32-bytes-long-test', 30, 86400);
    }
    
    public function testConstructorThrowsExceptionWhenRefreshTokenShorterThanAccess(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token lifetime must be greater than access token lifetime');
        
        new JwtConfig('test-secret-32-bytes-long-test', 7200, 3600);
    }
}