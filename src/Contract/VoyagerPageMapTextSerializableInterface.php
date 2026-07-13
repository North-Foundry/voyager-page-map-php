<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Contract;

use NorthFoundry\VoyagerPageMap\Serialization\SerializationContext;

/**
 * Defines deterministic text serialization for a VPM value or subtree.
 */
interface VoyagerPageMapTextSerializableInterface
{
    /**
     * Serializes the value, optionally starting from an existing indentation context.
     */
    public function toText(?SerializationContext $context = null): string;
}
