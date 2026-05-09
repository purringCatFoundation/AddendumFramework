<?php
declare(strict_types=1);

namespace PCF\Addendum\Entity\User;

use Countable;
use Ds\Vector;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, AdminAuditTrailEntry> */
final class AdminAuditTrail implements Countable, IteratorAggregate
{
    /** @var Vector<AdminAuditTrailEntry> */
    private Vector $entries;

    public function __construct(iterable $entries = [])
    {
        $this->entries = new Vector();

        foreach ($entries as $entry) {
            $this->add($entry);
        }
    }

    public function add(AdminAuditTrailEntry $entry): void
    {
        $this->entries->push($entry);
    }

    public function isEmpty(): bool
    {
        return $this->entries->isEmpty();
    }

    public function count(): int
    {
        return $this->entries->count();
    }

    /** @return Vector<AdminAuditTrailEntry> */
    public function all(): Vector
    {
        return $this->entries->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->entries->getIterator();
    }
}
