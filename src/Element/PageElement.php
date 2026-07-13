<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ElementAttributeCollection;

/**
 * Represents the synthetic root element included in every VPM/1 document.
 */
final class PageElement extends AbstractVoyagerPageMapElement
{
    protected function serializedTag(): string
    {
        return 'page';
    }

    protected function serializedAttributes(): ElementAttributeCollection
    {
        $attributes = new ElementAttributeCollection();

        foreach (['lang'] as $name) {
            if ($this->has($name)) {
                $attributes->add($name, $this->value($name));
            }
        }

        return $attributes;
    }
}
