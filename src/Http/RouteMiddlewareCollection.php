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
 * @implements IteratorAggregate<int, RouteMiddleware>
 * @implements ArrayAccess<int, RouteMiddleware>
 */
final class RouteMiddlewareCollection implements Countable, IteratorAggregate, ArrayAccess
{
    /** @var Vector<RouteMiddleware> */
    private Vector $middlewares;

    public function __construct(iterable $middlewares = [])
    {
        $this->middlewares = new Vector();

        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    public static function of(RouteMiddleware ...$middlewares): self
    {
        return new self($middlewares);
    }

    public function add(RouteMiddleware $middleware): void
    {
        $this->middlewares->push($middleware);
    }

    public function merge(self $other): self
    {
        $merged = new self($this->middlewares);

        foreach ($other as $middleware) {
            $merged->add($middleware);
        }

        return $merged;
    }

    public function reversed(): self
    {
        $copy = $this->middlewares->copy();
        $copy->reverse();

        return new self($copy);
    }

    public function isEmpty(): bool
    {
        return $this->middlewares->isEmpty();
    }

    public function count(): int
    {
        return $this->middlewares->count();
    }

    /**
     * @return Vector<RouteMiddleware>
     */
    public function all(): Vector
    {
        return $this->middlewares->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->middlewares->getIterator();
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < $this->middlewares->count();
    }

    public function offsetGet(mixed $offset): RouteMiddleware
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException('Route middleware offset is out of range');
        }

        return $this->middlewares->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new InvalidArgumentException('Route middleware collection is immutable through array access');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new InvalidArgumentException('Route middleware collection is immutable through array access');
    }
}
