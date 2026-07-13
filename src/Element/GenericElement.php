<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ActionAvailability;
use NorthFoundry\VoyagerPageMap\Model\ElementActionCollection;

/**
 * Represents a retained element without a dedicated VPM class, including
 * generic containers that expose static interactive semantics.
 */
final class GenericElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        $interactive = in_array($rawAttributes['role'] ?? null, ['button', 'link', 'menuitem'], true)
            || array_key_exists('href', $rawAttributes)
            || array_key_exists('onclick', $rawAttributes);

        return $interactive ? ElementContentMode::Name : ElementContentMode::Children;
    }

    protected function serializedTag(): string
    {
        return $this->sourceTag() ?? 'div';
    }

    protected function serializedActions(): ElementActionCollection
    {
        $actions = new ElementActionCollection();
        $hasDestination = $this->has('href');
        $interactive = $hasDestination || in_array($this->value('role'), ['button', 'link', 'menuitem'], true) || $this->has('onclick');

        if ($interactive) {
            $actions->add('click', $this->disabled() ? ActionAvailability::Blocked : ActionAvailability::Unverified);
        }

        if ($hasDestination) {
            $actions->add('open', $this->disabled() ? ActionAvailability::Blocked : ActionAvailability::Unverified);
        }

        if ($interactive || $this->has('tabindex')) {
            $this->focusAction($actions);
        }

        return $actions;
    }
}
