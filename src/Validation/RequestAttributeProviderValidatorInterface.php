<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use Ds\Map;

interface RequestAttributeProviderValidatorInterface extends RequestValidatorInterface
{
    /** @return Map<string, mixed> */
    public function requestAttributes(mixed $value): Map;
}
