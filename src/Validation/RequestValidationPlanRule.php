<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use Ds\Vector;
use InvalidArgumentException;

final readonly class RequestValidationPlanRule
{
    /** @var Vector<RequestValidatorInterface> */
    private Vector $validators;

    public function __construct(
        public string $fieldName,
        public RequestFieldSource $source,
        iterable $validators
    ) {
        $this->validators = new Vector();

        foreach ($validators as $validator) {
            if (!$validator instanceof RequestValidatorInterface) {
                throw new InvalidArgumentException('Validation plan rule accepts only request validators');
            }

            $this->validators->push($validator);
        }
    }

    /**
     * @return Vector<RequestValidatorInterface>
     */
    public function validators(): Vector
    {
        return $this->validators->copy();
    }
}
