<?php
declare(strict_types=1);

namespace PCF\Addendum\Entity\User;

use Countable;
use Ds\Vector;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, ActiveAdmin> */
final class ActiveAdminCollection implements Countable, IteratorAggregate
{
    /** @var Vector<ActiveAdmin> */
    private Vector $admins;

    public function __construct(iterable $admins = [])
    {
        $this->admins = new Vector();

        foreach ($admins as $admin) {
            $this->add($admin);
        }
    }

    public function add(ActiveAdmin $admin): void
    {
        $this->admins->push($admin);
    }

    public function isEmpty(): bool
    {
        return $this->admins->isEmpty();
    }

    public function count(): int
    {
        return $this->admins->count();
    }

    /** @return Vector<ActiveAdmin> */
    public function all(): Vector
    {
        return $this->admins->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->admins->getIterator();
    }
}
