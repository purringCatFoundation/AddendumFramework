<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use InvalidArgumentException;
use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Validation\RequestValidationConstraintInterface;
use PCF\Addendum\Validation\RequestValidatorInterface;
use PCF\Addendum\Validation\RequestValidatorProviderInterface;

final readonly class JwtTokenValidatorProvider implements RequestValidatorProviderInterface
{
    public function __construct(
        private JwtConfigFactory $configFactory,
        private TokenValidationRepositoryFactory $tokenValidationRepositoryFactory
    ) {
    }

    public function supports(RequestValidationConstraintInterface $constraint): bool
    {
        return $constraint instanceof JwtToken;
    }

    public function create(RequestValidationConstraintInterface $constraint): RequestValidatorInterface
    {
        if (!$constraint instanceof JwtToken) {
            throw new InvalidArgumentException(sprintf('%s is not a JWT token constraint', $constraint::class));
        }

        return new JwtTokenValidator(
            $this->configFactory->create(),
            $this->tokenValidationRepositoryFactory->create(),
            $constraint->requiredTokenType()
        );
    }
}
