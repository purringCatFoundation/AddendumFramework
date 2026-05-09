<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use ArrayAccess;
use Countable;
use Ds\Vector;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, RegisteredRoute>
 * @implements ArrayAccess<int, RegisteredRoute>
 */
final class RegisteredRouteCollection implements Countable, IteratorAggregate, ArrayAccess
{
    /** @var Vector<RegisteredRoute> */
    private Vector $routes;

    public function __construct(iterable $routes = [])
    {
        $this->routes = new Vector();

        foreach ($routes as $route) {
            $this->add($route);
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    public function add(RegisteredRoute $route): void
    {
        $this->routes->push($route);
    }

    public function isEmpty(): bool
    {
        return $this->routes->isEmpty();
    }

    public function count(): int
    {
        return $this->routes->count();
    }

    /**
     * @return Vector<RegisteredRoute>
     */
    public function all(): Vector
    {
        return $this->routes->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->routes->getIterator();
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < $this->routes->count();
    }

    public function offsetGet(mixed $offset): RegisteredRoute
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException('Registered route offset is out of range');
        }

        return $this->routes->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new InvalidArgumentException('Registered route collection is immutable through array access');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new InvalidArgumentException('Registered route collection is immutable through array access');
    }
}
