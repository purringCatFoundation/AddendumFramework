<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(
        public string $middlewareClass,
        public array $options = []
    ) {
    }
}
