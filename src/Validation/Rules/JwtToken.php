<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use PCF\Addendum\Auth\TokenType;
use PCF\Addendum\Validation\RequestValidationConstraintInterface;

final readonly class JwtToken implements RequestValidationConstraintInterface
{
    public function __construct(
        private string $requiredTokenType = TokenType::USER
    ) {
    }

    public function requiredTokenType(): string
    {
        return $this->requiredTokenType;
    }
}
