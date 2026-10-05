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

    /** @param iterable<array-key, string|int|float|bool|null> $parameters */
    public function __construct(iterable $parameters = [])
    {
        $this->parameters = self::normalizeParameters($parameters);
    }

    /**
     * @param iterable<array-key, string|int|float|bool|null> $parameters
     * @return Map<string, string>
     */
    private static function normalizeParameters(iterable $parameters): Map
    {
        $namedParameters = new Map();

        foreach ($parameters as $name => $value) {
            if (is_string($name)) {
                $namedParameters->put($name, (string) $value);
            }
        }

        return $namedParameters;
    }

    /** @param iterable<array-key, string> $matches */
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
