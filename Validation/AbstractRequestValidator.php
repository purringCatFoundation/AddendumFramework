<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Validation;

use CitiesRpg\ApiBackend\Api\Validation\RequestValidatorInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractRequestValidator implements RequestValidatorInterface
{
    /**
     * Check if extracted value is valid
     *
     * @param mixed $value Extracted value from request
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        return $this->validate($value) === null;
    }
}