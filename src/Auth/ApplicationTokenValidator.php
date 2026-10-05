<?php

declare(strict_types=1);

namespace PCF\Addendum\Auth;

use PCF\Addendum\Repository\User\ApplicationTokenRepository;

class ApplicationTokenValidator
{
    public function __construct(
        private readonly ApplicationTokenRepository $repository,
        private readonly ApplicationTokenHashCache $cache
    ) {
    }

    public function isKnown(string $jti, string $token): bool
    {
        $actualHash = hash('sha256', $token);
        $knownHash = $this->cache->get($jti);

        if ($knownHash === null) {
            $knownHash = $this->repository->findHashByJti($jti);

            if ($knownHash === null) {
                return false;
            }

            $this->cache->remember($jti, $knownHash);
        }

        return hash_equals($knownHash, $actualHash);
    }

    public function remember(string $jti, string $token): void
    {
        $this->cache->remember($jti, hash('sha256', $token));
    }
}
