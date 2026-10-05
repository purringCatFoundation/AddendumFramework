<?php

declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route
{
    /** @param array<string, string> $requirements */
    public function __construct(
        public string $path,
        public string $method,
        public array $requirements = []
    ) {
    }
}
