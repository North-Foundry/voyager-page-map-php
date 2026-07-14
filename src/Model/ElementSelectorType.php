<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

/**
 * Selection strategy used to locate a retained VPM node in its source DOM.
 */
enum ElementSelectorType: string
{
    case Css = 'css';
    case XPath = 'xpath';
}
