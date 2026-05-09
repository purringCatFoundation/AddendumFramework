<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use ArrayIterator;
use Countable;
use Ds\Vector;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements IteratorAggregate<int, RequestValidationRule>
 */
final readonly class RequestValidationRuleCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var Vector<RequestValidationRule> */
    private Vector $rules;

    public function __construct(iterable $rules = [])
    {
        $this->rules = new Vector();

        foreach ($rules as $rule) {
            if (!$rule instanceof RequestValidationRule) {
                throw new InvalidArgumentException('Validation rule collection accepts only request validation rules');
            }

            $this->rules->push($rule);
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    public static function of(RequestValidationRule ...$rules): self
    {
        return new self($rules);
    }

    /**
     * @return Vector<RequestValidationRule>
     */
    public function all(): Vector
    {
        return $this->rules->copy();
    }

    public function count(): int
    {
        return $this->rules->count();
    }

    public function isEmpty(): bool
    {
        return $this->rules->isEmpty();
    }

    public function getIterator(): Traversable
    {
        return $this->rules->getIterator();
    }

    public function jsonSerialize(): array
    {
        return $this->rules->toArray();
    }
}
