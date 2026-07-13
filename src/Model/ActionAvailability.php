<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

/**
 * Describes how confidently a static document can advertise an element action.
 */
enum ActionAvailability: string
{
    case Available = 'available';
    case Unverified = 'unverified';
    case Blocked = 'blocked';
    case Unsupported = 'unsupported';
}
