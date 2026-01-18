<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Attribute;

use Attribute;
use CitiesRpg\ApiBackend\Api\Validation\RequestValidatorInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ValidateRequest
{
    public const string SOURCE_BODY = 'body';
    public const string SOURCE_QUERY = 'query';
    public const string SOURCE_HEADER = 'header';

    /** @var RequestValidatorInterface[] */
    public readonly array $validators;
    public readonly string $source;

    /**
     * Create validation rule for a field
     *
     * Examples:
     * - new ValidateRequest('email', new Required(), new Email())
     * - new ValidateRequest('token', new JwtToken(), ValidateRequest::SOURCE_HEADER)
     */
    public function __construct(
        public readonly string $fieldName,
        RequestValidatorInterface|string ...$validatorsAndSource,
    ) {
        // Last parameter can be source string, rest are validators
        $params = $validatorsAndSource;
        $lastParam = end($params);

        if (is_string($lastParam) && in_array($lastParam, [self::SOURCE_BODY, self::SOURCE_QUERY, self::SOURCE_HEADER], true)) {
            // Last param is source - remove it and keep the rest as validators
            $this->source = array_pop($params);
            $this->validators = array_values($params);
        } else {
            // No source specified, use default
            $this->source = self::SOURCE_BODY;
            $this->validators = array_values($params);
        }
    }

    /**
     * Get the source for the field value
     */
    public function getSource(): string
    {
        return $this->source;
    }
}