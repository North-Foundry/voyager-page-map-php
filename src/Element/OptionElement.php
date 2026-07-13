<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ElementAttributeCollection;

/**
 * Serializes an individual select option and its declared selection state.
 */
final class OptionElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::Name;
    }

    protected function serializedTag(): string
    {
        return 'option';
    }

    protected function serializedAttributes(): ElementAttributeCollection
    {
        $a = new ElementAttributeCollection();
        foreach (['value', 'label'] as $n) {
            if ($this->has($n)) {
                $a->add($n, $this->value($n));
            }
        } if ($this->has('selected')) {
            $a->add('selected');
        } if ($this->disabled()) {
            $a->add('disabled');
        } return $this->appendGeneral($a);
    }
}
