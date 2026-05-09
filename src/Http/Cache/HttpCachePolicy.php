<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Ds\Vector;

final readonly class HttpCachePolicy
{
    /** @var Vector<string> */
    public Vector $vary;

    /** @var Vector<string> */
    public Vector $tags;

    /** @var Vector<string> */
    public Vector $resources;

    /** @var Vector<string> */
    public Vector $invalidate;

    /**
     * @param iterable<string> $vary
     * @param iterable<string> $tags
     * @param iterable<string> $resources
     * @param iterable<string> $invalidate
     */
    public function __construct(
        public HttpCacheMode $mode,
        public ?int $maxAge,
        public ?int $sharedMaxAge,
        public ?int $staleWhileRevalidate,
        public ?int $staleIfError,
        iterable $vary,
        iterable $tags,
        public bool $cacheErrors,
        iterable $resources = [],
        iterable $invalidate = []
    ) {
        $this->vary = $vary instanceof Vector ? $vary->copy() : new Vector($vary);
        $this->tags = $tags instanceof Vector ? $tags->copy() : new Vector($tags);
        $this->resources = $resources instanceof Vector ? $resources->copy() : new Vector($resources);
        $this->invalidate = $invalidate instanceof Vector ? $invalidate->copy() : new Vector($invalidate);
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
