<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Exception\Html;

use RuntimeException;

/**
 * Thrown when supplied HTML cannot produce a usable document tree.
 */
final class HtmlParsingException extends RuntimeException {}
