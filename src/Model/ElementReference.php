<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

use InvalidArgumentException;

/**
 * Value object for the one-based {@code @e<number>} address of a VPM node and
 * its optional selector in the source DOM.
 */
final readonly class ElementReference
{
    public function __construct(public int $number, public ?ElementSelector $selector = null)
    {
        if ($number < 1) {
            throw new InvalidArgumentException('An element reference number must be greater than zero.');
        }
    }

    /**
     * Returns the CSS selector when this reference addresses a DOM element.
     */
    public function cssSelector(): ?string
    {
        return $this->selector?->type === ElementSelectorType::Css
            ? $this->selector->value
            : null;
    }

    /**
     * Returns the XPath selector when this reference addresses a non-CSS node.
     */
    public function xpathSelector(): ?string
    {
        return $this->selector?->type === ElementSelectorType::XPath
            ? $this->selector->value
            : null;
    }

    /**
     * Formats the reference in the textual VPM address notation.
     */
    public function __toString(): string
    {
        return '@e' . $this->number;
    }
}
