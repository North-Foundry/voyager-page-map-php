<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

/**
 * Represents one list item, whose descendant text is serialized as its name.
 */
final class ListItemElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::TextOrChildren;
    }

    protected function serializedTag(): string
    {
        return 'li';
    }
}
