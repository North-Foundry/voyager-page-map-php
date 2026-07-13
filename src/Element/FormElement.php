<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ElementAttributeCollection;

/**
 * Serializes form submission metadata and the form's retained controls.
 */
final class FormElement extends AbstractVoyagerPageMapElement
{
    protected function serializedTag(): string
    {
        return 'form';
    }

    protected function serializedAttributes(): ElementAttributeCollection
    {
        $a = new ElementAttributeCollection();
        foreach (['action', 'method', 'name', 'autocomplete', 'enctype'] as $n) {
            if ($this->has($n)) {
                $a->add($n, $this->value($n));
            }
        } if ($this->has('novalidate')) {
            $a->add('novalidate');
        } return $this->appendGeneral($a);
    }
}
