<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

/**
 * Serializes images while retaining accessibility attributes by default.
 */
final class ImageElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::None;
    }

    protected function serializedTag(): string
    {
        return 'img';
    }
}
