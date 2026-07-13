<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Document;

/**
 * Stores document-level values extracted during the build process.
 */
final readonly class VoyagerPageMapDocumentMetadata
{
    public function __construct(public ?string $title, public ?string $language, public ?string $baseUrl) {}
}
