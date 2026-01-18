<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Config;

use InvalidArgumentException;

class JwtConfig
{
    public function __construct(
        public readonly string $secret,
        public readonly int $accessTokenLifetime,
        public readonly int $refreshTokenLifetime
    ) {
        if (empty($this->secret)) {
            throw new InvalidArgumentException('JWT secret cannot be empty');
        }
        
        if ($this->accessTokenLifetime < 60) {
            throw new InvalidArgumentException('Access token lifetime must be at least 60 seconds');
        }
        
        if ($this->refreshTokenLifetime < $this->accessTokenLifetime) {
            throw new InvalidArgumentException('Refresh token lifetime must be greater than access token lifetime');
        }
    }
}