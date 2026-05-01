<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class HttpCacheRequestContext
{
    public function __construct(
        public bool $authenticated,
        public ?string $userUuid,
        public ?string $tokenType,
        public string $authState,
        public ?string $userContextHash,
        public bool $trustedUserContext
    ) {
    }
}
