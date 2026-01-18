<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        public string $path,
        public string $method,
        public array $requirements = []
    ) {
    }
}
