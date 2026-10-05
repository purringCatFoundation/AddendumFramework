<?php

declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class ValidateField
{
    /** @param array<array-key, mixed> $rules */
    public function __construct(
        public string $field,
        public array $rules = [],
        public string $message = ''
    ) {
    }
}
