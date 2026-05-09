<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use Ds\Map;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, string>
 */
final readonly class RouteParameters implements IteratorAggregate
{
    /** @var Map<string, string> */
    private Map $parameters;

    public function __construct(iterable $parameters = [])
    {
        $this->parameters = new Map();

        foreach ($parameters as $name => $value) {
            if (is_string($name)) {
                $this->parameters->put($name, (string) $value);
            }
        }
    }

    public static function fromRegexMatches(iterable $matches): self
    {
        return new self($matches);
    }

    public function get(string $name): ?string
    {
        return $this->parameters->hasKey($name) ? $this->parameters->get($name) : null;
    }

    public function isEmpty(): bool
    {
        return $this->parameters->isEmpty();
    }

    /**
     * @return Map<string, string>
     */
    public function all(): Map
    {
        return $this->parameters->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->parameters->getIterator();
    }

    /**
     * Boundary method for PSR request attributes and legacy consumers.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->parameters->toArray();
    }
}
