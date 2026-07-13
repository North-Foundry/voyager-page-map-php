<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Ordered mutable collection of VPM properties emitted by an element.
 *
 * @implements IteratorAggregate<int, ElementAttribute>
 */
final class ElementAttributeCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<ElementAttribute>
     */
    private array $attributes = [];

    /**
     * Appends a property, preserving the declared serialization order.
     */
    public function add(string $name, ?string $value = null): void
    {
        $this->attributes[] = new ElementAttribute($name, $value);
    }

    /**
     * @return Traversable<int, ElementAttribute>
     */
    public function getIterator(): Traversable
    {
        yield from $this->attributes;
    }

    public function count(): int
    {
        return count($this->attributes);
    }
}
