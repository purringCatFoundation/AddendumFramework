<?php

declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public string $middlewareClass,
        public array $options = []
    ) {
    }
}
