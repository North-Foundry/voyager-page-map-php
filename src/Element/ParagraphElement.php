<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

/**
 * Represents a paragraph, serialized as one named text-bearing leaf.
 */
final class ParagraphElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::TextOrChildren;
    }

    protected function serializedTag(): string
    {
        return 'p';
    }
}
