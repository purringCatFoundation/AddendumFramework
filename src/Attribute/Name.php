<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Name
{
    public function __construct(
        public string $value
    ) {
    }
}
