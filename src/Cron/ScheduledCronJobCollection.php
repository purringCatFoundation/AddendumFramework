<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use Countable;
use Ds\Vector;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, ScheduledCronJob> */
final class ScheduledCronJobCollection implements Countable, IteratorAggregate
{
    /** @var Vector<ScheduledCronJob> */
    private Vector $jobs;

    public function __construct(iterable $jobs = [])
    {
        $this->jobs = new Vector();

        foreach ($jobs as $job) {
            $this->add($job);
        }
    }

    public function add(ScheduledCronJob $job): void
    {
        $this->jobs->push($job);
    }

    public function isEmpty(): bool
    {
        return $this->jobs->isEmpty();
    }

    public function count(): int
    {
        return $this->jobs->count();
    }

    /** @return Vector<ScheduledCronJob> */
    public function all(): Vector
    {
        return $this->jobs->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->jobs->getIterator();
    }
}
