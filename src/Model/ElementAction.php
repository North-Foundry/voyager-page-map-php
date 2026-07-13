<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

/**
 * Stores one VPM action name together with its static availability.
 */
final readonly class ElementAction
{
    public function __construct(public string $name, public ActionAvailability $availability = ActionAvailability::Unverified) {}
}
