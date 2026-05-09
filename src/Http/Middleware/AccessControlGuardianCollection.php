<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use ArrayAccess;
use Countable;
use Ds\Vector;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, AccessControlGuardianDefinitionInterface>
 * @implements ArrayAccess<int, AccessControlGuardianDefinitionInterface>
 */
final class AccessControlGuardianCollection implements Countable, IteratorAggregate, ArrayAccess
{
    /** @var Vector<AccessControlGuardianDefinitionInterface> */
    private Vector $guardians;

    public function __construct(iterable $guardians = [])
    {
        $this->guardians = new Vector();

        foreach ($guardians as $guardian) {
            $this->add($guardian);
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    public function add(AccessControlGuardianDefinitionInterface $guardian): void
    {
        $this->guardians->push($guardian);
    }

    public function isEmpty(): bool
    {
        return $this->guardians->isEmpty();
    }

    public function count(): int
    {
        return $this->guardians->count();
    }

    /**
     * @return Vector<AccessControlGuardianDefinitionInterface>
     */
    public function all(): Vector
    {
        return $this->guardians->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->guardians->getIterator();
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < $this->guardians->count();
    }

    public function offsetGet(mixed $offset): AccessControlGuardianDefinitionInterface
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException('Access control guardian offset is out of range');
        }

        return $this->guardians->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new InvalidArgumentException('Access control guardian collection is immutable through array access');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new InvalidArgumentException('Access control guardian collection is immutable through array access');
    }
}
