<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Attribute;
use PCF\Addendum\Http\Cache\HttpCacheMode;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class ResourcePolicy
{
    public function __construct(
        public HttpCacheMode $mode = HttpCacheMode::PUBLIC,
        public int $maxAge = 0,
        public string $resource = '',
        public ?string $idAttribute = null,
        public bool $cacheErrors = false
    ) {
    }
}
