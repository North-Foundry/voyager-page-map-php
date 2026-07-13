<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

/**
 * Tri-state boolean used for runtime facts that may be unavailable in static HTML.
 */
enum KnownBoolean: string
{
    case True = 'true';
    case False = 'false';
    case Unknown = 'unknown';
}
