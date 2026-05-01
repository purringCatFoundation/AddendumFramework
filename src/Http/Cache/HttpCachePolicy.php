<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final readonly class HttpCachePolicy
{
    /**
     * @param list<string> $vary
     * @param list<string> $tags
     * @param list<string> $resources
     * @param list<string> $invalidate
     */
    public function __construct(
        public HttpCacheMode $mode,
        public ?int $maxAge,
        public ?int $sharedMaxAge,
        public ?int $staleWhileRevalidate,
        public ?int $staleIfError,
        public array $vary,
        public array $tags,
        public bool $cacheErrors,
        public array $resources = [],
        public array $invalidate = []
    ) {
    }

    public static function privateNoStore(): self
    {
        return new self(
            mode: HttpCacheMode::PRIVATE,
            maxAge: null,
            sharedMaxAge: null,
            staleWhileRevalidate: null,
            staleIfError: null,
            vary: [],
            tags: [],
            cacheErrors: false,
            resources: [],
            invalidate: []
        );
    }

    public function redisTtl(): ?int
    {
        $ttl = $this->sharedMaxAge ?? $this->maxAge;

        return $ttl !== null && $ttl > 0 ? $ttl : null;
    }
}
