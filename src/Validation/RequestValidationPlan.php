<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use ArrayIterator;
use Countable;
use Ds\Vector;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, RequestValidationPlanRule>
 */
final readonly class RequestValidationPlan implements Countable, IteratorAggregate
{
    /** @var Vector<RequestValidationPlanRule> */
    private Vector $rules;

    public function __construct(iterable $rules = [])
    {
        $this->rules = new Vector();

        foreach ($rules as $rule) {
            if (!$rule instanceof RequestValidationPlanRule) {
                throw new InvalidArgumentException('Validation plan accepts only validation plan rules');
            }

            $this->rules->push($rule);
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @return Vector<RequestValidationPlanRule>
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
}
