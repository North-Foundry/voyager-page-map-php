<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ActionAvailability;
use NorthFoundry\VoyagerPageMap\Model\ElementActionCollection;
use NorthFoundry\VoyagerPageMap\Model\ElementAttributeCollection;

/**
 * Serializes select controls, their declared constraints, and selection action.
 */
final class SelectElement extends AbstractVoyagerPageMapElement
{
    protected function serializedTag(): string
    {
        return 'select';
    }

    protected function serializedAttributes(): ElementAttributeCollection
    {
        $a = new ElementAttributeCollection();
        foreach (['name', 'autocomplete'] as $n) {
            if ($this->has($n)) {
                $a->add($n, $this->value($n));
            }
        } foreach (['multiple', 'required', 'autofocus'] as $n) {
            if ($this->has($n)) {
                $a->add($n);
            }
        } if ($this->disabled()) {
            $a->add('disabled');
        } return $this->appendGeneral($a);
    }

    protected function serializedActions(): ElementActionCollection
    {
        $a = new ElementActionCollection();
        $s = $this->disabled() ? ActionAvailability::Blocked : ActionAvailability::Unverified;
        $a->add('select', $s);
        $a->add('focus', $s);
        return $a;
    }
}
