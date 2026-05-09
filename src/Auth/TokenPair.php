<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

final readonly class TokenPair
{
    private bool $admin;

    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
        public string $tokenType = 'Bearer',
        bool $admin = false
    ) {
        $this->admin = $admin;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }
}
