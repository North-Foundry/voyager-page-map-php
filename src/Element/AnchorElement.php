<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ActionAvailability;
use NorthFoundry\VoyagerPageMap\Model\ElementActionCollection;

/**
 * Serializes hyperlinks and their static navigation actions.
 */
final class AnchorElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::Name;
    }

    protected function serializedTag(): string
    {
        return 'a';
    }

    protected function serializedActions(): ElementActionCollection
    {
        $a = new ElementActionCollection();

        if ($this->has('href')) {
            $state = $this->disabled() ? ActionAvailability::Blocked : ActionAvailability::Unverified;
            $a->add('click', $state);
            $a->add('open', $state);
        }

        return $a;
    }
}
