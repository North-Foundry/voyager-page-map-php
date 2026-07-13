<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

/**
 * Represents an informative DOM text node retained outside a named leaf element.
 */
final class TextElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::None;
    }

    protected function serializedTag(): string
    {
        return 'text';
    }
}
