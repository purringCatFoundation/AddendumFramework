<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

final readonly class TokenPair
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public string $tokenType = 'Bearer',
        public bool $isAdmin = false,
        public ?string $characterUuid = null
    ) {
    }
}
