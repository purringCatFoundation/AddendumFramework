<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use Countable;
use Ds\Map;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<string, SchedulableCron> */
final class SchedulableCronCollection implements Countable, IteratorAggregate
{
    /** @var Map<string, SchedulableCron> */
    private Map $crons;

    public function __construct(iterable $crons = [])
    {
        $this->crons = new Map();

        foreach ($crons as $cron) {
            $this->add($cron);
        }
    }

    public function add(SchedulableCron $cron): void
    {
        $this->crons->put($cron->code, $cron);
    }

    public function get(string $code): ?SchedulableCron
    {
        return $this->crons->hasKey($code) ? $this->crons->get($code) : null;
    }

    public function isEmpty(): bool
    {
        return $this->crons->isEmpty();
    }

    public function count(): int
    {
        return $this->crons->count();
    }

    /** @return Map<string, SchedulableCron> */
    public function all(): Map
    {
        return $this->crons->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->crons->getIterator();
    }
}
