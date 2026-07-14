<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

use InvalidArgumentException;

/**
 * A typed selector that resolves one retained VPM reference in the source DOM.
 */
final readonly class ElementSelector
{
    public function __construct(public ElementSelectorType $type, public string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('An element selector cannot be empty.');
        }
    }

    public static function css(string $value): self
    {
        return new self(ElementSelectorType::Css, $value);
    }

    public static function xpath(string $value): self
    {
        return new self(ElementSelectorType::XPath, $value);
    }
}
