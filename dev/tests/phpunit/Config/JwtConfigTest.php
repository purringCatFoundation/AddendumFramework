<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Config;

use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Tests\Support\JwtKeyPair;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class JwtConfigTest extends TestCase
{
    public function testConstructorWithValidValues(): void
    {
        $keyPair = JwtKeyPair::shared();
        $config = new JwtConfig($keyPair->privateKeyPath, $keyPair->publicKeyPath, $keyPair->privateKeyPassphrase, 7200, 1209600);
        
        $this->assertSame($keyPair->privateKeyPath, $config->privateKeyPath);
        $this->assertSame($keyPair->publicKeyPath, $config->publicKeyPath);
        $this->assertNull($config->privateKeyPassphrase);
        $this->assertSame(7200, $config->accessTokenLifetime);
        $this->assertSame(1209600, $config->refreshTokenLifetime);
    }
    
    public function testConstructorThrowsExceptionForEmptyPrivateKeyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT private key path cannot be empty');
        
        new JwtConfig('', JwtKeyPair::shared()->publicKeyPath, null, 3600, 86400);
    }

    public function testConstructorThrowsExceptionForUnreadablePublicKeyPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT public key path must point to a readable file');

        new JwtConfig(JwtKeyPair::shared()->privateKeyPath, '/missing/jwt_public.pem', null, 3600, 86400);
    }
    
    public function testConstructorThrowsExceptionForShortAccessTokenLifetime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token lifetime must be at least 60 seconds');
        
        $keyPair = JwtKeyPair::shared();

        new JwtConfig($keyPair->privateKeyPath, $keyPair->publicKeyPath, $keyPair->privateKeyPassphrase, 30, 86400);
    }
    
    public function testConstructorThrowsExceptionWhenRefreshTokenShorterThanAccess(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token lifetime must be greater than access token lifetime');
        
        $keyPair = JwtKeyPair::shared();

        new JwtConfig($keyPair->privateKeyPath, $keyPair->publicKeyPath, $keyPair->privateKeyPassphrase, 7200, 3600);
    }
}
