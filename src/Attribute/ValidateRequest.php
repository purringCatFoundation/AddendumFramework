<?php
declare(strict_types=1);

namespace PCF\Addendum\Attribute;

use Attribute;
use InvalidArgumentException;
use PCF\Addendum\Validation\RequestFieldSource;
use PCF\Addendum\Validation\RequestValidationConstraintCollection;
use PCF\Addendum\Validation\RequestValidationConstraintInterface;
use PCF\Addendum\Validation\RequestValidationRule;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ValidateRequest
{
    public const string SOURCE_BODY = 'body';
    public const string SOURCE_QUERY = 'query';
    public const string SOURCE_HEADER = 'header';

    public readonly RequestValidationConstraintCollection $constraints;
    public readonly RequestFieldSource $source;

    /**
     * Create validation rule for a field
     *
     * Examples:
     * - new ValidateRequest('email', new Required(), new Email())
     * - new ValidateRequest('token', new JwtToken(), ValidateRequest::SOURCE_HEADER)
     */
    public function __construct(
        public readonly string $fieldName,
        RequestValidationConstraintInterface|RequestFieldSource|string ...$constraintsAndSource,
    ) {
        $params = $constraintsAndSource;
        $lastParam = end($params);

        if ($lastParam instanceof RequestFieldSource) {
            $this->source = array_pop($params);
        } elseif (is_string($lastParam) && in_array($lastParam, [self::SOURCE_BODY, self::SOURCE_QUERY, self::SOURCE_HEADER], true)) {
            $this->source = RequestFieldSource::fromString(array_pop($params));
        } else {
            $this->source = RequestFieldSource::Body;
        }

        foreach ($params as $param) {
            if (!$param instanceof RequestValidationConstraintInterface) {
                throw new InvalidArgumentException('ValidateRequest accepts validation constraints followed by an optional source');
            }
        }

        $this->constraints = new RequestValidationConstraintCollection(array_values($params));
    }

    public function toRule(): RequestValidationRule
    {
        return new RequestValidationRule($this->fieldName, $this->source, $this->constraints);
    }

    /**
     * Get the source for the field value
     */
    public function getSource(): string
    {
        return $this->source->value;
    }
}
