<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ActionAvailability;
use NorthFoundry\VoyagerPageMap\Model\ElementActionCollection;
use NorthFoundry\VoyagerPageMap\Model\ElementAttributeCollection;

/**
 * Serializes native buttons, including their type, disabled state, and actions.
 */
final class ButtonElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::Name;
    }

    protected function serializedTag(): string
    {
        return 'button';
    }

    protected function serializedAttributes(): ElementAttributeCollection
    {
        $a = new ElementAttributeCollection();
        $a->add('type', $this->value('type') ?? 'submit');
        foreach (['name', 'value'] as $n) {
            if ($this->has($n)) {
                $a->add($n, $this->value($n));
            }
        } if ($this->disabled()) {
            $a->add('disabled');
        } return $this->appendGeneral($a);
    }

    protected function serializedActions(): ElementActionCollection
    {
        $a = new ElementActionCollection();
        $s = $this->disabled() ? ActionAvailability::Blocked : ActionAvailability::Unverified;
        $a->add('click', $s);
        $a->add('focus', $s);
        return $a;
    }
}
