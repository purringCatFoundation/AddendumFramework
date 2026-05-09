<?php
declare(strict_types=1);

namespace PCF\Addendum\Exception;

use Ds\Map;
use Exception;

class ValidationException extends Exception
{
    /** @var Map<string, mixed> */
    private Map $errors;

    public function __construct(
        iterable $errors,
        string $message = 'Validation failed'
    ) {
        $this->errors = $errors instanceof Map ? $errors->copy() : new Map($errors);
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors->toArray();
    }
}
