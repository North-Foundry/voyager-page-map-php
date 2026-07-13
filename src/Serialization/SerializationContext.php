<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Serialization;

/**
 * Carries indentation state while a VPM subtree is serialized recursively.
 */
final readonly class SerializationContext
{
    public function __construct(public int $indentation = 0) {}

    /**
     * Returns the context for one nesting level below the current element.
     */
    public function child(): self
    {
        return new self($this->indentation + 1);
    }
}
