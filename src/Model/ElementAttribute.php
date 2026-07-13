<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

/**
 * Stores one serialized VPM property name and its optional value.
 */
final readonly class ElementAttribute
{
    public function __construct(public string $name, public ?string $value = null) {}
}
