<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

use InvalidArgumentException;

/**
 * Value object for the one-based {@code @e<number>} address of a VPM node.
 */
final readonly class ElementReference
{
    public function __construct(public int $number)
    {
        if ($number < 1) {
            throw new InvalidArgumentException('An element reference number must be greater than zero.');
        }
    }

    /**
     * Formats the reference in the textual VPM address notation.
     */
    public function __toString(): string
    {
        return '@e' . $this->number;
    }
}
