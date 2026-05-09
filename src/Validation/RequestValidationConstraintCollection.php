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
 * @implements IteratorAggregate<int, RequestValidationConstraintInterface>
 */
final readonly class RequestValidationConstraintCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var Vector<RequestValidationConstraintInterface> */
    private Vector $constraints;

    public function __construct(iterable $constraints = [])
    {
        $this->constraints = new Vector();

        foreach ($constraints as $constraint) {
            if (!$constraint instanceof RequestValidationConstraintInterface) {
                throw new InvalidArgumentException('Validation constraint collection accepts only request validation constraints');
            }

            $this->constraints->push($constraint);
        }
    }

    public static function of(RequestValidationConstraintInterface ...$constraints): self
    {
        return new self($constraints);
    }

    /**
     * @return Vector<RequestValidationConstraintInterface>
     */
    public function all(): Vector
    {
        return $this->constraints->copy();
    }

    public function count(): int
    {
        return $this->constraints->count();
    }

    public function getIterator(): Traversable
    {
        return $this->constraints->getIterator();
    }

    public function jsonSerialize(): array
    {
        return array_map(
            static fn(RequestValidationConstraintInterface $constraint): array => ['class' => $constraint::class],
            $this->constraints->toArray()
        );
    }
}
