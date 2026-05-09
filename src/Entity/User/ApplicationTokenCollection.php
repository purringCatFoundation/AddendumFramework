<?php
declare(strict_types=1);

namespace PCF\Addendum\Entity\User;

use Countable;
use Ds\Vector;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, ApplicationToken> */
final class ApplicationTokenCollection implements Countable, IteratorAggregate
{
    /** @var Vector<ApplicationToken> */
    private Vector $tokens;

    public function __construct(iterable $tokens = [])
    {
        $this->tokens = new Vector();

        foreach ($tokens as $token) {
            $this->add($token);
        }
    }

    public function add(ApplicationToken $token): void
    {
        $this->tokens->push($token);
    }

    public function isEmpty(): bool
    {
        return $this->tokens->isEmpty();
    }

    public function count(): int
    {
        return $this->tokens->count();
    }

    /** @return Vector<ApplicationToken> */
    public function all(): Vector
    {
        return $this->tokens->copy();
    }

    public function getIterator(): Traversable
    {
        return $this->tokens->getIterator();
    }
}
