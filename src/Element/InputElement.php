<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ActionAvailability;
use NorthFoundry\VoyagerPageMap\Model\ElementActionCollection;
use NorthFoundry\VoyagerPageMap\Model\ElementAttributeCollection;

/**
 * Serializes native input controls and derives their static state and possible
 * actions from the declared input type and attributes.
 */
final class InputElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::None;
    }

    private function type(): string
    {
        return strtolower($this->value('type') ?? 'text');
    }

    protected function serializedTag(): string
    {
        return 'input';
    }

    protected function serializedAttributes(): ElementAttributeCollection
    {
        $a = new ElementAttributeCollection();
        $type = $this->type();
        $a->add('type', $type);
        foreach (['name', 'placeholder', 'min', 'max', 'step', 'minlength', 'maxlength', 'pattern', 'autocomplete', 'accept'] as $n) {
            if ($this->has($n)) {
                $a->add($n, $this->value($n));
            }
        }
        if ($type !== 'password' && $type !== 'hidden' && $this->has('value')) {
            $a->add('value', $this->value('value'));
        }
        foreach (['required', 'readonly', 'multiple', 'autofocus'] as $n) {
            if ($this->has($n)) {
                $a->add($n);
            }
        }
        if ($this->disabled()) {
            $a->add('disabled');
        }
        if (in_array($type, ['checkbox', 'radio'], true)) {
            $a->add($this->has('checked') ? 'checked' : 'unchecked');
        }
        if ($type === 'password') {
            $a->add('secret');
        }
        if (in_array($type, ['text', 'search', 'email', 'password', 'tel', 'url', 'number', 'range', 'date', 'datetime-local', 'month', 'week', 'time', 'color', 'file'], true)) {
            $a->add($this->has('value') ? 'filled' : 'empty');
        }
        return $this->appendGeneral($a);
    }

    protected function serializedActions(): ElementActionCollection
    {
        $a = new ElementActionCollection();
        $type = $this->type();
        $state = $this->disabled() ? ActionAvailability::Blocked : ActionAvailability::Unverified;
        if (in_array($type, ['checkbox', 'radio'], true)) {
            $a->add($this->has('checked') ? 'uncheck' : 'check', $state);
            $a->add('focus', $state);
            return $a;
        }
        if ($type === 'file') {
            $a->add('upload', $state);
            $a->add('focus', $state);
            return $a;
        }
        if (in_array($type, ['submit', 'reset', 'button', 'image'], true)) {
            $a->add('click', $state);
            $a->add('focus', $state);
            return $a;
        }
        if ($type !== 'hidden') {
            $a->add('fill', $state);
            $a->add('clear', $state);
            $a->add('focus', $state);
        }
        return $a;
    }
}
