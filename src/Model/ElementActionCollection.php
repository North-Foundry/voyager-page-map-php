<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Ordered mutable collection of actions emitted by a VPM element.
 *
 * @implements IteratorAggregate<int, ElementAction>
 */
final class ElementActionCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<ElementAction>
     */
    private array $actions = [];

    /**
     * Appends an action in the order in which it should be serialized.
     */
    public function add(string $name, ActionAvailability $availability = ActionAvailability::Unverified): void
    {
        $this->actions[] = new ElementAction($name, $availability);
    }

    /**
     * @return Traversable<int, ElementAction>
     */
    public function getIterator(): Traversable
    {
        yield from $this->actions;
    }

    public function count(): int
    {
        return count($this->actions);
    }
}
