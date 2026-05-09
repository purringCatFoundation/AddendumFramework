<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Compiled;

use PCF\Addendum\Validation\RequestValidationConstraintInterface;

interface RequestValidationConstraintExporterInterface
{
    public function supports(RequestValidationConstraintInterface $constraint): bool;

    public function export(RequestValidationConstraintInterface $constraint): string;
}
