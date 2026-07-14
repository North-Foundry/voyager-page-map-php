<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap;

use NorthFoundry\VoyagerPageMap\Configuration\VoyagerPageMapConfiguration;
use NorthFoundry\VoyagerPageMap\Contract\VoyagerPageMapElementInterface;
use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocument;
use NorthFoundry\VoyagerPageMap\Html\DomDocumentHtmlParser;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;

/**
 * Public entry point for converting static HTML into a deterministic VPM/1
 * document and querying the resulting element tree.
 */
final readonly class VoyagerPageMap
{
    private function __construct(private VoyagerPageMapDocument $document) {}

    /**
     * Creates a page map from HTML through the native DOM parser.
     */
    public static function fromHtml(
        string $html,
        ?string $baseUrl = null,
        ?VoyagerPageMapConfiguration $configuration = null,
    ): self {
        return new self(
            (new DomDocumentHtmlParser())
                ->parse(
                    $html,
                    $baseUrl,
                    $configuration ?? VoyagerPageMapConfiguration::default(),
                ),
        );
    }

    /**
     * Returns the immutable VPM document model.
     */
    public function document(): VoyagerPageMapDocument
    {
        return $this->document;
    }

    /**
     * Serializes the complete VPM document.
     */
    public function toText(): string
    {
        return $this->document->toText();
    }

    /**
     * Finds a retained element by a reference such as {@code @e5}.
     */
    public function findByReference(string $reference): ?VoyagerPageMapElementInterface
    {
        return $this->document->findByReference($reference);
    }

    /**
     * Resolves a compact reference such as {@code e5} or {@code @e5}.
     */
    public function ref(string $reference): ?ElementReference
    {
        return $this->document->ref($reference);
    }

    /**
     * Returns whether a compact or textual reference exists in this page map.
     */
    public function hasRef(string $reference): bool
    {
        return $this->document->hasRef($reference);
    }
}
