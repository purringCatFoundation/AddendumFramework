<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use Countable;
use Ds\Map;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<string, CronDefinition> */
final class CronDefinitionCollection implements Countable, IteratorAggregate
{
    /** @var Map<string, CronDefinition> */
    private Map $definitions;

    public function __construct(iterable $definitions = [])
    {
        $this->definitions = new Map();

        foreach ($definitions as $definition) {
            $this->add($definition);
        }
    }

    public function add(CronDefinition $definition): void
    {
        $this->definitions->put($definition->code, $definition);
    }

    public function get(string $code): ?CronDefinition
    {
        return $this->definitions->hasKey($code) ? $this->definitions->get($code) : null;
    }

    public function isEmpty(): bool
    {
        return $this->definitions->isEmpty();
    }

    public function count(): int
    {
        return $this->definitions->count();
    }

    /** @return Map<string, CronDefinition> */
    public function all(): Map
    {
        return $this->definitions->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->definitions->getIterator();
    }
}
