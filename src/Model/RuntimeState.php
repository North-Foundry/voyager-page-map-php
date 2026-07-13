<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

/**
 * Holds optional browser-derived facts that are deliberately unknown in the
 * static VPM/1 implementation.
 */
final readonly class RuntimeState
{
    public function __construct(
        public KnownBoolean $visible = KnownBoolean::Unknown,
        public KnownBoolean $inViewport = KnownBoolean::Unknown,
        public KnownBoolean $occluded = KnownBoolean::Unknown,
        public KnownBoolean $receivesPointerEvents = KnownBoolean::Unknown,
        public KnownBoolean $focused = KnownBoolean::Unknown,
        public KnownBoolean $stable = KnownBoolean::Unknown,
    ) {}
}
