<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

/**
 * Represents one native heading level and serializes its text as the name.
 */
final class HeadingElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::TextOrChildren;
    }

    protected function serializedTag(): string
    {
        return $this->sourceTag() ?? 'h1';
    }
}
