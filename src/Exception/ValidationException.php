<?php

declare(strict_types=1);

namespace PCF\Addendum\Exception;

use Ds\Map;
use Exception;

class ValidationException extends Exception
{
    /** @var Map<string, mixed> */
    private Map $errors;

    /** @param iterable<string, mixed> $errors */
    public function __construct(
        iterable $errors,
        string $message = 'Validation failed'
    ) {
        $this->errors = $errors instanceof Map ? $errors->copy() : new Map($errors);
        parent::__construct($message);
    }

    /** @return array<string, mixed> */
    public function getErrors(): array
    {
        return $this->errors->toArray();
    }
}
