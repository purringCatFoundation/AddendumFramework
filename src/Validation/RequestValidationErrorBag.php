<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use Ds\Map;
use Ds\Vector;

final class RequestValidationErrorBag
{
    /** @var Map<string, Vector<string>> */
    private Map $errors;

    public function __construct()
    {
        $this->errors = new Map();
    }

    public function add(string $fieldName, string $error): void
    {
        if (!$this->errors->hasKey($fieldName)) {
            $this->errors->put($fieldName, new Vector());
        }

        $this->errors->get($fieldName)->push($error);
    }

    public function isEmpty(): bool
    {
        return $this->errors->isEmpty();
    }

    /**
     * @return array<string, list<string>>
     */
    public function toArray(): array
    {
        $errors = [];

        foreach ($this->errors as $fieldName => $fieldErrors) {
            $errors[$fieldName] = $fieldErrors->toArray();
        }

        return $errors;
    }
}
