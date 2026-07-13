<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

/**
 * Describes how an element turns its source HTML content into VPM data.
 */
enum ElementContentMode
{
    /** Retain meaningful DOM descendants as child VPM elements. */
    case Children;

    /** Absorb descendant readable text into this element's name. */
    case Name;

    /** Use a compact name unless structured descendants must be retained. */
    case TextOrChildren;

    /** Ignore DOM descendants because the element is intrinsically a leaf. */
    case None;
}
