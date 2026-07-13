<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

/**
 * Represents a label that was retained instead of being absorbed by a control.
 */
final class LabelElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::Name;
    }

    protected function serializedTag(): string
    {
        return 'label';
    }
}
