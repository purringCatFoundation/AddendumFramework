<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use InvalidArgumentException;

final readonly class SelfValidatingConstraintProvider implements RequestValidatorProviderInterface
{
    public function supports(RequestValidationConstraintInterface $constraint): bool
    {
        return $constraint instanceof RequestValidatorInterface;
    }

    public function create(RequestValidationConstraintInterface $constraint): RequestValidatorInterface
    {
        if (!$constraint instanceof RequestValidatorInterface) {
            throw new InvalidArgumentException(sprintf('%s is not a request validator', $constraint::class));
        }

        return $constraint;
    }
}
