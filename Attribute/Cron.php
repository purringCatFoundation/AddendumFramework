<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Cron
{
    public function __construct(
        public string $code,
        public string $expression = '* * * * *'
    ) {
    }
}
