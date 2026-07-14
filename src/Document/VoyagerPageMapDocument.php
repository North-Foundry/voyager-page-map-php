<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Document;

use NorthFoundry\VoyagerPageMap\Contract\VoyagerPageMapElementInterface;
use NorthFoundry\VoyagerPageMap\Element\PageElement;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;
use NorthFoundry\VoyagerPageMap\Serialization\TextEscaper;

/**
 * Owns the synthetic page root and document metadata after conversion has
 * completed, without retaining a dependency on the source DOM.
 */
final readonly class VoyagerPageMapDocument
{
    /**
     * @var array<string, VoyagerPageMapElementInterface>
     */
    private array $elementsByReference;

    public function __construct(public PageElement $page, public VoyagerPageMapDocumentMetadata $metadata)
    {
        $elementsByReference = [];
        $index = function (VoyagerPageMapElementInterface $element) use (&$elementsByReference, &$index): void {
            $elementsByReference[(string) $element->reference()] = $element;
            foreach ($element->children() as $child) {
                $index($child);
            }
        };
        $index($page);
        $this->elementsByReference = $elementsByReference;
    }

    /**
     * Serializes the complete document with the VPM/1 header and final newline.
     */
    public function toText(): string
    {
        $header = ['VPM/1'];
        if ($this->metadata->title !== null && $this->metadata->title !== '') {
            $header[] = 'title ' . TextEscaper::token($this->metadata->title);
        }
        if ($this->metadata->baseUrl !== null && $this->metadata->baseUrl !== '') {
            $header[] = 'url ' . TextEscaper::token($this->metadata->baseUrl);
        }

        return implode("\n", $header) . "\n\n" . $this->page->toText() . "\n";
    }

    /**
     * Finds an element by its textual reference, such as {@code @e5}.
     */
    public function findByReference(string $reference): ?VoyagerPageMapElementInterface
    {
        return $this->elementsByReference[$reference] ?? null;
    }

    /**
     * Resolves the compact {@code e5} or textual {@code @e5} notation.
     */
    public function ref(string $reference): ?ElementReference
    {
        $reference = trim($reference);
        if (preg_match('/^@?e([1-9]\d*)$/iD', $reference, $matches) !== 1) {
            return null;
        }

        return $this->findByReference('@e' . $matches[1])?->reference();
    }

    /**
     * Returns whether a compact or textual reference exists in this document.
     */
    public function hasRef(string $reference): bool
    {
        return $this->ref($reference) !== null;
    }
}
