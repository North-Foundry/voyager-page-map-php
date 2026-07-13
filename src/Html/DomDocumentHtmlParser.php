<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Html;

use DOMDocument;
use DOMElement;
use DOMNamedNodeMap;
use DOMNode;
use DOMText;
use DOMXPath;
use NorthFoundry\VoyagerPageMap\Configuration\VoyagerPageMapConfiguration;
use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocument;
use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocumentMetadata;
use NorthFoundry\VoyagerPageMap\Element\AbstractVoyagerPageMapElement;
use NorthFoundry\VoyagerPageMap\Element\AnchorElement;
use NorthFoundry\VoyagerPageMap\Element\ButtonElement;
use NorthFoundry\VoyagerPageMap\Element\ElementContentMode;
use NorthFoundry\VoyagerPageMap\Element\FormElement;
use NorthFoundry\VoyagerPageMap\Element\GenericElement;
use NorthFoundry\VoyagerPageMap\Element\HeadingElement;
use NorthFoundry\VoyagerPageMap\Element\ImageElement;
use NorthFoundry\VoyagerPageMap\Element\InputElement;
use NorthFoundry\VoyagerPageMap\Element\LabelElement;
use NorthFoundry\VoyagerPageMap\Element\ListItemElement;
use NorthFoundry\VoyagerPageMap\Element\OptionElement;
use NorthFoundry\VoyagerPageMap\Element\PageElement;
use NorthFoundry\VoyagerPageMap\Element\ParagraphElement;
use NorthFoundry\VoyagerPageMap\Element\SelectElement;
use NorthFoundry\VoyagerPageMap\Element\TableCellElement;
use NorthFoundry\VoyagerPageMap\Element\TextareaElement;
use NorthFoundry\VoyagerPageMap\Element\TextElement;
use NorthFoundry\VoyagerPageMap\Exception\Html\HtmlParsingException;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;

/**
 * Converts one HTML source string into a VPM document through PHP's native DOM.
 *
 * This is the sole HTML extraction boundary: parsing, DOM traversal, readable
 * name resolution, source attributes, and element selection stay here.
 */
final class DomDocumentHtmlParser
{
    /**
     * Only tags with VPM-specific behavior need a dedicated class. Every other
     * retained tag is represented by GenericElement with its source tag intact.
     *
     * @var array<string, class-string<AbstractVoyagerPageMapElement>>
     */
    private const REGISTRY = [
        'a' => AnchorElement::class,
        'button' => ButtonElement::class,
        'form' => FormElement::class,
        'h1' => HeadingElement::class,
        'h2' => HeadingElement::class,
        'h3' => HeadingElement::class,
        'h4' => HeadingElement::class,
        'h5' => HeadingElement::class,
        'h6' => HeadingElement::class,
        'img' => ImageElement::class,
        'input' => InputElement::class,
        'label' => LabelElement::class,
        'li' => ListItemElement::class,
        'option' => OptionElement::class,
        'p' => ParagraphElement::class,
        'select' => SelectElement::class,
        'td' => TableCellElement::class,
        'textarea' => TextareaElement::class,
        'th' => TableCellElement::class,
    ];

    /** @var list<string> DOM subtrees that can never contribute static page-map content. */
    private const IGNORED_TAGS = ['script', 'style', 'noscript', 'meta', 'link', 'base', 'template', 'head', 'svg'];

    /** @var list<string> Controls supported by native HTML label association. */
    private const LABELABLE_TAGS = ['button', 'input', 'select', 'textarea'];

    /** @var list<string> Generic wrappers promoted away unless they carry semantics. */
    private const PROMOTABLE_TAGS = ['div', 'span'];

    /**
     * Inline formatting that can be safely folded into a text-bearing parent's
     * name. Any other retained descendant must remain independently addressable.
     *
     * @var list<string>
     */
    private const TEXT_FORMATTING_TAGS = ['abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'del', 'dfn', 'em', 'i', 'ins', 'kbd', 'mark', 'q', 'ruby', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr'];

    /** @var list<string> URL-valued attributes normalized during extraction. */
    private const URL_ATTRIBUTES = ['href', 'src', 'action'];

    private DOMXPath $xpath;

    private VoyagerPageMapConfiguration $configuration;

    private ?string $baseUrl;

    private int $reference = 0;

    /**
     * Parses one source string and constructs a self-contained VPM document.
     *
     * Parser state is reset on every call, so one parser instance can be reused
     * without leaking references, configuration, URLs, or XPath state.
     */
    public function parse(string $html, ?string $baseUrl, VoyagerPageMapConfiguration $configuration): VoyagerPageMapDocument
    {
        $document = $this->loadDocument($html);
        $root = $document->documentElement;
        if (!$root instanceof DOMElement) {
            throw new HtmlParsingException('The supplied HTML could not be parsed into a usable DOM document.');
        }

        $this->xpath = new DOMXPath($document);
        $this->configuration = $configuration;
        $this->baseUrl = $baseUrl;
        $this->reference = 0;

        $title = $this->normalize($this->firstElementText('//title'));
        $language = $this->normalize($root->getAttribute('lang'));
        $attributes = [];
        if ($language !== '') {
            $attributes['lang'] = $language;
        }
        // Allocate the page before descendants to preserve final preorder IDs.
        $pageReference = $this->nextReference();
        $body = $this->firstElement('//body') ?? $root;

        $page = new PageElement(
            $pageReference,
            rawAttributes: $attributes,
            children: $this->buildChildren($body),
        );

        return new VoyagerPageMapDocument(
            $page,
            new VoyagerPageMapDocumentMetadata(
                $title === '' ? null : $title,
                $language === '' ? null : $language,
                $baseUrl,
            ),
        );
    }

    /**
     * Parses tolerant UTF-8 HTML without leaking libxml diagnostics globally.
     */
    private function loadDocument(string $html): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $document = new DOMDocument('1.0', 'UTF-8');
            // The declaration forces libxml's HTML parser to interpret the PHP
            // string as UTF-8; it is not retained as a page-map node.
            $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html, \LIBXML_NOERROR | \LIBXML_NOWARNING | \LIBXML_NONET | \LIBXML_COMPACT);
            if (!$loaded || $document->documentElement === null) {
                throw new HtmlParsingException('The supplied HTML could not be parsed into a usable DOM document.');
            }

            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @return list<AbstractVoyagerPageMapElement> Retained VPM children in source order.
     */
    private function buildChildren(DOMNode $parent): array
    {
        $result = [];
        foreach ($parent->childNodes as $node) {
            foreach ($this->buildNode($node) as $element) {
                $result[] = $element;
            }
        }

        return $result;
    }

    /**
     * @return list<AbstractVoyagerPageMapElement> The VPM nodes retained from one DOM node.
     */
    private function buildNode(DOMNode $node): array
    {
        if ($node instanceof DOMText) {
            $text = $this->normalize($node->data);

            return $text !== '' ? [new TextElement($this->nextReference(), $text)] : [];
        }
        if (!$node instanceof DOMElement) {
            return [];
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, self::IGNORED_TAGS, true)) {
            return [];
        }
        if ($tag === 'input' && strtolower($node->getAttribute('type')) === 'hidden') {
            return [];
        }
        if (!$this->configuration->includeHiddenElements && ($node->hasAttribute('hidden') || strtolower($node->getAttribute('aria-hidden')) === 'true')) {
            return [];
        }
        if ($this->isConsumedLabel($node)) {
            return $node->hasAttribute('for') ? [] : $this->buildContainedFormControls($node);
        }
        if (!$this->shouldKeep($node)) {
            return $this->buildChildren($node);
        }

        $element = $this->createElement($node);

        return [$element];
    }

    /**
     * Retains controls nested by a wrapping label without emitting the label's naming text twice.
     *
     * @return list<AbstractVoyagerPageMapElement>
     */
    private function buildContainedFormControls(DOMElement $label): array
    {
        $result = [];
        $walk = function (DOMNode $parent) use (&$result, &$walk): void {
            foreach ($parent->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }
                if (in_array(strtolower($child->tagName), self::LABELABLE_TAGS, true)) {
                    foreach ($this->buildNode($child) as $element) {
                        $result[] = $element;
                    }
                    continue;
                }
                $walk($child);
            }
        };
        $walk($label);

        return $result;
    }

    /**
     * Decides whether a DOM element is semantically relevant to the VPM output.
     */
    private function shouldKeep(DOMElement $element): bool
    {
        $tag = strtolower($element->tagName);
        if (!in_array($tag, self::PROMOTABLE_TAGS, true)) {
            return true;
        }
        if ($this->configuration->includeGenericContainers) {
            return true;
        }
        foreach (['role', 'tabindex', 'contenteditable', 'aria-label', 'aria-labelledby', 'onclick'] as $attribute) {
            if ($element->hasAttribute($attribute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether a label should be consumed to avoid duplicate output.
     */
    private function isConsumedLabel(DOMElement $element): bool
    {
        if (strtolower($element->tagName) !== 'label') {
            return false;
        }

        return $element->hasAttribute('for') || $this->containsFormControl($element);
    }

    /**
     * Detects a form control nested inside a label.
     */
    private function containsFormControl(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if (in_array(strtolower($child->tagName), self::LABELABLE_TAGS, true) || $this->containsFormControl($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates the corresponding typed VPM element from one retained DOM element.
     */
    private function createElement(DOMElement $element): AbstractVoyagerPageMapElement
    {
        $tag = strtolower($element->tagName);
        $attributes = $this->attributes($element->attributes);
        foreach (self::URL_ATTRIBUTES as $url) {
            if (isset($attributes[$url])) {
                $attributes[$url] = $this->resolveUrl($attributes[$url]);
            }
        }
        $class = self::REGISTRY[$tag] ?? GenericElement::class;
        $contentMode = $class::contentMode($attributes);
        $retainsChildren = $contentMode === ElementContentMode::Children
            || ($contentMode === ElementContentMode::TextOrChildren && $this->hasStructuredDescendant($element));
        $usesContentName = $contentMode === ElementContentMode::Name
            || ($contentMode === ElementContentMode::TextOrChildren && !$retainsChildren);
        $reference = $this->nextReference();

        return new $class(
            reference: $reference,
            name: $this->resolveName($element, $usesContentName),
            rawAttributes: $attributes,
            sourceTag: $tag,
            includedSourceAttributes: $this->configuration->includeAttributes,
            content: $tag === 'textarea' ? $this->normalize($element->textContent) : null,
            children: $retainsChildren ? $this->buildChildren($element) : [],
        );
    }

    /**
     * Detects descendants whose semantics would be lost by flattening all content into a name.
     */
    private function hasStructuredDescendant(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::IGNORED_TAGS, true)) {
                continue;
            }
            if ($tag === 'input' && strtolower($child->getAttribute('type')) === 'hidden') {
                continue;
            }
            if (!$this->configuration->includeHiddenElements && ($child->hasAttribute('hidden') || strtolower($child->getAttribute('aria-hidden')) === 'true')) {
                continue;
            }
            if (in_array($tag, self::PROMOTABLE_TAGS, true) && !$this->shouldKeep($child)) {
                if ($this->hasStructuredDescendant($child)) {
                    return true;
                }
                continue;
            }
            if (!in_array($tag, self::TEXT_FORMATTING_TAGS, true)) {
                return true;
            }
            if ($this->hasStructuredDescendant($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string> Lowercase DOM attribute names indexed by name.
     */
    private function attributes(DOMNamedNodeMap $attributes): array
    {
        $result = [];
        foreach ($attributes as $attribute) {
            $result[strtolower($attribute->nodeName)] = $attribute->nodeValue ?? '';
        }

        return $result;
    }

    /**
     * Resolves a static accessible name using ARIA, labels, contents, and fallbacks.
     */
    private function resolveName(DOMElement $element, bool $allowContents = true): ?string
    {
        $labelledBy = $this->normalize($element->getAttribute('aria-labelledby'));
        if ($labelledBy !== '') {
            $labels = [];
            foreach (preg_split('/\s+/', $labelledBy) ?: [] as $id) {
                $node = $this->firstElement('//*[@id=' . $this->xpathLiteral($id) . ']');
                if ($node !== null) {
                    $labels[] = $this->readableText($node);
                }
            }
            $name = $this->normalize(implode(' ', $labels));
            if ($name !== '') {
                return $name;
            }
        }
        $ariaLabel = $this->normalize($element->getAttribute('aria-label'));
        if ($ariaLabel !== '') {
            return $ariaLabel;
        }
        if ($element->hasAttribute('id') && $this->isLabelable($element)) {
            $labels = [];
            $nodes = $this->xpath->query('//label[@for=' . $this->xpathLiteral($element->getAttribute('id')) . ']');
            if ($nodes !== false) {
                foreach ($nodes as $label) {
                    if (!$label instanceof DOMElement) {
                        continue;
                    }
                    $name = $this->readableText($label);
                    if ($name !== '') {
                        $labels[] = $name;
                    }
                }
            }
            $name = $this->normalize(implode(' ', $labels));
            if ($name !== '') {
                return $name;
            }
        }
        if ($this->isLabelable($element)) {
            for ($parent = $element->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode) {
                if (strtolower($parent->tagName) === 'label') {
                    $name = $this->readableText($parent, $element);
                    if ($name !== '') {
                        return $name;
                    }
                    break;
                }
            }
        }
        if (strtolower($element->tagName) === 'img') {
            $alt = $this->normalize($element->getAttribute('alt'));
            if ($alt !== '') {
                return $alt;
            }
        }
        $title = $this->normalize($element->getAttribute('title'));
        if ($title !== '') {
            return $title;
        }
        if ($allowContents) {
            $text = $this->readableText($element);
            if ($text !== '') {
                return $text;
            }
        }
        foreach (['placeholder', 'name'] as $attribute) {
            $value = $this->normalize($element->getAttribute($attribute));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns whether native HTML label association applies to an element.
     */
    private function isLabelable(DOMElement $element): bool
    {
        return in_array(strtolower($element->tagName), self::LABELABLE_TAGS, true);
    }

    /**
     * Returns normalized descendant text while excluding non-readable HTML nodes.
     */
    private function readableText(DOMNode $node, ?DOMNode $excludedNode = null): string
    {
        $parts = [];
        $walk = function (DOMNode $current) use (&$parts, &$walk, $excludedNode): void {
            if ($current === $excludedNode) {
                return;
            }
            if ($current instanceof DOMElement && in_array(strtolower($current->tagName), ['script', 'style', 'noscript'], true)) {
                return;
            }
            if ($current instanceof DOMElement && strtolower($current->tagName) === 'img') {
                $parts[] = $current->getAttribute('alt');

                return;
            }
            if ($current instanceof DOMElement && strtolower($current->tagName) === 'input' && strtolower($current->getAttribute('type')) === 'image') {
                $parts[] = $current->getAttribute('alt');

                return;
            }
            if ($current instanceof DOMText) {
                $parts[] = $current->data;
            }
            foreach ($current->childNodes as $child) {
                $walk($child);
            }
        };
        $walk($node);

        return $this->normalize(implode(' ', $parts));
    }

    /**
     * Compacts a URL against the document URL when relative resolution is enabled.
     *
     * Same-origin destinations are expressed from the page path itself, so a
     * page at /browse/benefits links to the origin root as ../../. External
     * destinations retain their original absolute or protocol-relative form.
     */
    private function resolveUrl(string $value): string
    {
        if (!$this->configuration->resolveRelativeUrls || $this->baseUrl === null || $value === '') {
            return $value;
        }
        $base = parse_url($this->baseUrl);
        if ($base === false || !isset($base['scheme'], $base['host'])) {
            return $value;
        }

        if (str_starts_with($value, '#') || str_starts_with($value, '?')) {
            return $value;
        }

        $absolute = $this->absoluteUrl($value, $base);
        $target = parse_url($absolute);
        if ($target === false || !isset($target['scheme'], $target['host']) || !$this->hasSameOrigin($base, $target)) {
            return $value;
        }

        $basePath = $base['path'] ?? '/';
        $targetPath = $target['path'] ?? '/';
        if ($basePath === $targetPath) {
            $baseQuery = $base['query'] ?? null;
            $targetQuery = $target['query'] ?? null;
            if ($baseQuery === $targetQuery && isset($target['fragment'])) {
                return '#' . $target['fragment'];
            }
            if ($targetQuery !== null) {
                return '?' . $targetQuery . (isset($target['fragment']) ? '#' . $target['fragment'] : '');
            }
        }

        $relative = $this->relativePath($basePath, $targetPath);
        if (isset($target['query'])) {
            $relative .= '?' . $target['query'];
        }
        if (isset($target['fragment'])) {
            $relative .= '#' . $target['fragment'];
        }

        return $relative;
    }

    /**
     * @param array<string, int|string> $base
     */
    private function absoluteUrl(string $value, array $base): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $value) === 1) {
            return $value;
        }
        if (str_starts_with($value, '//')) {
            return $base['scheme'] . ':' . $value;
        }

        $relative = parse_url($value);
        if ($relative === false) {
            return $value;
        }
        $authority = $base['scheme'] . '://';
        if (isset($base['user'])) {
            $authority .= $base['user'] . (isset($base['pass']) ? ':' . $base['pass'] : '') . '@';
        }
        $authority .= $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '');
        $basePath = isset($base['path']) && is_string($base['path']) ? $base['path'] : '/';

        if (str_starts_with($value, '#')) {
            $url = $authority . $basePath;
            if (isset($base['query'])) {
                $url .= '?' . $base['query'];
            }

            return $url . $value;
        }
        if (str_starts_with($value, '?')) {
            return $authority . $basePath . $value;
        }

        $relativePath = $relative['path'] ?? '';
        $mergedPath = str_starts_with($relativePath, '/')
            ? $relativePath
            : substr($basePath, 0, strrpos($basePath, '/') + 1) . $relativePath;
        $url = $authority . $this->removeDotSegments($mergedPath);
        if (isset($relative['query'])) {
            $url .= '?' . $relative['query'];
        }
        if (isset($relative['fragment'])) {
            $url .= '#' . $relative['fragment'];
        }

        return $url;
    }

    /**
     * @param array<string, int|string> $base
     * @param array<string, int|string> $target
     */
    private function hasSameOrigin(array $base, array $target): bool
    {
        $baseScheme = strtolower((string) ($base['scheme'] ?? ''));
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));

        return $baseScheme === $targetScheme
            && strtolower((string) ($base['host'] ?? '')) === strtolower((string) ($target['host'] ?? ''))
            && ($base['port'] ?? $this->defaultPort($baseScheme)) === ($target['port'] ?? $this->defaultPort($targetScheme));
    }

    private function defaultPort(string $scheme): ?int
    {
        return match ($scheme) {
            'http' => 80,
            'https' => 443,
            default => null,
        };
    }

    /**
     * Produces a compact path relative to every segment of the current page.
     */
    private function relativePath(string $basePath, string $targetPath): string
    {
        $baseSegments = $this->pathSegments($basePath);
        $targetSegments = $this->pathSegments($targetPath);
        $common = 0;
        $maximum = min(count($baseSegments), count($targetSegments));
        while ($common < $maximum && $baseSegments[$common] === $targetSegments[$common]) {
            ++$common;
        }

        $relative = str_repeat('../', count($baseSegments) - $common)
            . implode('/', array_slice($targetSegments, $common));
        if ($relative === '') {
            return '.';
        }
        if (str_ends_with($targetPath, '/') && !str_ends_with($relative, '/')) {
            $relative .= '/';
        }

        return $relative;
    }

    /** @return list<string> */
    private function pathSegments(string $path): array
    {
        $trimmed = trim($this->removeDotSegments($path), '/');

        return $trimmed === '' ? [] : explode('/', $trimmed);
    }

    /**
     * Normalizes dot segments in a URL path while preserving its absolute shape.
     */
    private function removeDotSegments(string $path): string
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }
        $normalized = '/' . implode('/', $segments);

        return str_ends_with($path, '/') && $normalized !== '/' ? $normalized . '/' : $normalized;
    }

    /**
     * Returns the first element matching an internal XPath expression.
     */
    private function firstElement(string $query): ?DOMElement
    {
        $nodes = $this->xpath->query($query);
        if ($nodes === false) {
            return null;
        }
        $node = $nodes->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    /**
     * Returns the text content of the first element matching an XPath expression.
     */
    private function firstElementText(string $query): ?string
    {
        return $this->firstElement($query)?->textContent;
    }

    /**
     * Produces an XPath string literal for an arbitrary HTML attribute value.
     */
    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'{$value}'";
        }
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        $parts = array_map(static fn(string $part): string => "'{$part}'", explode("'", $value));

        return 'concat(' . implode(", \"'\", ", $parts) . ')';
    }

    /**
     * Decodes HTML entities, collapses Unicode whitespace, and trims the result.
     */
    private function normalize(?string $text): string
    {
        $decoded = html_entity_decode($text ?? '', \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $decoded));
    }

    /**
     * Allocates the next one-based VPM element reference.
     */
    private function nextReference(): ElementReference
    {
        return new ElementReference(++$this->reference);
    }
}
