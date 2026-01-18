<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Validation;

use Psr\Http\Message\ServerRequestInterface;

interface RequestValidatorInterface
{
    /**
     * Validate extracted value and return validation error if any.
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string;

    /**
     * Check if extracted value is valid.
     *
     * @param mixed $value Extracted value from request
     * @return bool
     */
    public function isValid(mixed $value): bool;
}