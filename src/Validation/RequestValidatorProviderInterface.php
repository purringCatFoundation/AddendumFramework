<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

interface RequestValidatorProviderInterface
{
    public function supports(RequestValidationConstraintInterface $constraint): bool;

    public function create(RequestValidationConstraintInterface $constraint): RequestValidatorInterface;
}
