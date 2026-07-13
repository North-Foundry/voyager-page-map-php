<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

/**
 * Represents a table data or header cell as a named leaf.
 */
final class TableCellElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::TextOrChildren;
    }

    protected function serializedTag(): string
    {
        return $this->sourceTag() ?? 'td';
    }
}
