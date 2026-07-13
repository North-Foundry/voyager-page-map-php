<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

use Countable;
use IteratorAggregate;
use NorthFoundry\VoyagerPageMap\Contract\VoyagerPageMapElementInterface;
use Traversable;

/**
 * Ordered read-only collection of child nodes in a VPM element tree.
 *
 * @implements IteratorAggregate<int, VoyagerPageMapElementInterface>
 */
final readonly class ElementCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<VoyagerPageMapElementInterface>
     */
    private array $elements;

    /**
     * @param iterable<VoyagerPageMapElementInterface> $elements Children in final document order.
     */
    public function __construct(iterable $elements = [])
    {
        $orderedElements = [];
        foreach ($elements as $element) {
            $orderedElements[] = $element;
        }
        $this->elements = $orderedElements;
    }

    /**
     * @return Traversable<int, VoyagerPageMapElementInterface>
     */
    public function getIterator(): Traversable
    {
        yield from $this->elements;
    }

    public function count(): int
    {
        return count($this->elements);
    }
}
