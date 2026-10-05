<?php
declare(strict_types=1);

namespace PCF\Addendum\Config;

use InvalidArgumentException;

class JwtConfig
{
    public function __construct(
        public readonly string $privateKeyPath,
        public readonly string $publicKeyPath,
        public readonly ?string $privateKeyPassphrase,
        public readonly int $accessTokenLifetime,
        public readonly int $refreshTokenLifetime
    ) {
        $this->assertReadableFile($this->privateKeyPath, 'JWT private key');
        $this->assertReadableFile($this->publicKeyPath, 'JWT public key');
        
        if ($this->accessTokenLifetime < 60) {
            throw new InvalidArgumentException('Access token lifetime must be at least 60 seconds');
        }
        
        if ($this->refreshTokenLifetime < $this->accessTokenLifetime) {
            throw new InvalidArgumentException('Refresh token lifetime must be greater than access token lifetime');
        }
    }

    private function assertReadableFile(string $path, string $label): void
    {
        if (trim($path) === '') {
            throw new InvalidArgumentException($label . ' path cannot be empty');
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException($label . ' path must point to a readable file');
        }
    }
}
