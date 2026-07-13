<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ActionAvailability;
use NorthFoundry\VoyagerPageMap\Model\ElementActionCollection;
use NorthFoundry\VoyagerPageMap\Model\ElementAttributeCollection;

/**
 * Serializes textarea controls, their declared constraints, and text-edit actions.
 */
final class TextareaElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::None;
    }

    protected function serializedTag(): string
    {
        return 'textarea';
    }

    protected function serializedAttributes(): ElementAttributeCollection
    {
        $a = new ElementAttributeCollection();
        foreach (['name', 'placeholder', 'minlength', 'maxlength', 'autocomplete', 'rows', 'cols'] as $n) {
            if ($this->has($n)) {
                $a->add($n, $this->value($n));
            }
        } foreach (['required', 'readonly', 'autofocus'] as $n) {
            if ($this->has($n)) {
                $a->add($n);
            }
        } if ($this->disabled()) {
            $a->add('disabled');
        }
        $content = $this->content();
        if ($content !== null && $content !== '') {
            $a->add('value', $content);
        }
        $a->add($content !== null && $content !== '' ? 'filled' : 'empty');
        return $this->appendGeneral($a);
    }

    protected function serializedActions(): ElementActionCollection
    {
        $a = new ElementActionCollection();
        $s = $this->disabled() ? ActionAvailability::Blocked : ActionAvailability::Unverified;
        $a->add('fill', $s);
        $a->add('clear', $s);
        $a->add('focus', $s);
        return $a;
    }
}
