<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Rules;

use Ds\Vector;
use PCF\Addendum\Validation\AbstractRequestValidator;

class In extends AbstractRequestValidator
{
    /** @var Vector<mixed> */
    private Vector $allowedValues;

    public function __construct(iterable $allowedValues)
    {
        $this->allowedValues = $allowedValues instanceof Vector
            ? $allowedValues->copy()
            : new Vector($allowedValues);
    }

    /** @return Vector<mixed> */
    public function allowedValues(): Vector
    {
        return $this->allowedValues->copy();
    }

    /**
     * Validate that value is in allowed list
     *
     * @param mixed $value Extracted value from request
     * @return string|null Error message or null if valid
     */
    public function validate(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!in_array($value, $this->allowedValues->toArray(), true)) {
            $allowedString = implode(', ', $this->allowedValues->toArray());
            return "Field must be one of: {$allowedString}";
        }

        return null;
    }
}
