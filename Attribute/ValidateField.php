<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class ValidateField
{
    public function __construct(
        public string $field,
        public array $rules = [],
        public string $message = ''
    ) {
    }
}