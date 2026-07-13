<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Contract;

use NorthFoundry\VoyagerPageMap\Model\ElementCollection;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;

/**
 * Represents one addressable node in a VPM document tree.
 */
interface VoyagerPageMapElementInterface extends VoyagerPageMapTextSerializableInterface
{
    /**
     * Returns the document-unique reference used to address this element.
     */
    public function reference(): ElementReference;

    /**
     * Returns the HTML-first tag emitted in VPM text.
     */
    public function tag(): string;

    /**
     * Returns the resolved readable name, when one is available.
     */
    public function name(): ?string;

    /**
     * Returns the retained child elements in document order.
     */
    public function children(): ElementCollection;
}
