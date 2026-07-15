<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Configuration;

use InvalidArgumentException;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ExceptionInterface as CssSelectorException;

/**
 * Controls optional retention and serialization choices made during VPM construction.
 *
 * Instances are immutable. Create a profile with a static constructor or create
 * a customized copy through the fluent {@code with...()} methods.
 *
 * @phpstan-consistent-constructor
 */
final readonly class VoyagerPageMapConfiguration
{
    /**
     * Whether to include all source attributes, none, or only named attributes.
     *
     * @var bool|list<string>
     */
    public bool|array $includeAttributes;

    /** @var list<string> */
    public array $ignoredSelectors;

    /**
     * Creates a configuration with explicitly selected options.
     *
     * @param bool|list<string> $includeAttributes True selects all source attributes.
     * @param list<string> $ignoredSelectors CSS selectors whose matching subtrees are excluded.
     */
    public function __construct(
        public bool $includeHiddenElements = false,
        public bool $includeGenericContainers = false,
        bool|array $includeAttributes = false,
        public bool $resolveRelativeUrls = false,
        array $ignoredSelectors = [],
    ) {
        $this->includeAttributes = self::normalizeAttributeSelection($includeAttributes);
        $this->ignoredSelectors = self::normalizeIgnoredSelectors($ignoredSelectors);
    }

    /**
     * Returns the default configuration, currently equivalent to the agent profile.
     */
    public static function default(): static
    {
        return static::agent();
    }

    /**
     * Returns the compact profile, currently equivalent to the agent profile.
     */
    public static function compact(): static
    {
        return static::agent();
    }

    /**
     * Returns a verbose map that retains hidden, generic, and source attribute information.
     */
    public static function diagnostic(): static
    {
        return new static(
            includeHiddenElements: true,
            includeGenericContainers: true,
            includeAttributes: true,
            resolveRelativeUrls: false,
        );
    }

    /**
     * Returns the profile intended for language-model and browser-agent consumption.
     */
    public static function agent(): static
    {
        return new static();
    }

    /**
     * Returns a copy that includes or omits hidden elements.
     */
    public function withHiddenElements(bool $enabled = true): static
    {
        return $this->copy(includeHiddenElements: $enabled);
    }

    /**
     * Returns a copy that includes or promotes generic div and span containers.
     */
    public function withGenericContainers(bool $enabled = true): static
    {
        return $this->copy(includeGenericContainers: $enabled);
    }

    /**
     * Returns a copy that includes all, none, or selected source attributes not otherwise emitted by VPM.
     *
     * @param bool|list<string> $attributes True selects all source attributes.
     */
    public function withAttributes(bool|array $attributes = true): static
    {
        return $this->copy(includeAttributes: $attributes);
    }

    /**
     * Returns a copy that enables or disables URL compaction relative to the document URL.
     */
    public function withRelativeUrlResolution(bool $enabled = true): static
    {
        return $this->copy(resolveRelativeUrls: $enabled);
    }

    /**
     * Returns a copy that excludes every subtree matching one of the CSS selectors.
     *
     * @param list<string> $selectors
     */
    public function withIgnoredSelectors(array $selectors): static
    {
        return $this->copy(ignoredSelectors: $selectors);
    }

    /**
     * Creates a copy while replacing only values explicitly provided by a fluent method.
     *
     * @param bool|list<string>|null $includeAttributes
     * @param list<string>|null $ignoredSelectors
     */
    private function copy(
        ?bool $includeHiddenElements = null,
        ?bool $includeGenericContainers = null,
        bool|array|null $includeAttributes = null,
        ?bool $resolveRelativeUrls = null,
        ?array $ignoredSelectors = null,
    ): static {
        $attributeSelection = $includeAttributes === null
            ? $this->includeAttributes
            : self::normalizeAttributeSelection($includeAttributes);

        return new static(
            includeHiddenElements: $includeHiddenElements ?? $this->includeHiddenElements,
            includeGenericContainers: $includeGenericContainers ?? $this->includeGenericContainers,
            includeAttributes: $attributeSelection,
            resolveRelativeUrls: $resolveRelativeUrls ?? $this->resolveRelativeUrls,
            ignoredSelectors: $ignoredSelectors ?? $this->ignoredSelectors,
        );
    }

    /**
     * Validates and canonicalizes configured source attribute names.
     *
     * @param bool|array<mixed> $attributes
     * @return bool|list<string>
     */
    private static function normalizeAttributeSelection(bool|array $attributes): bool|array
    {
        if (is_bool($attributes)) {
            return $attributes;
        }

        $normalized = [];
        foreach ($attributes as $attribute) {
            if (!is_string($attribute) || trim($attribute) === '') {
                throw new InvalidArgumentException('Configured attribute names must be non-empty strings.');
            }
            $normalized[] = strtolower(trim($attribute));
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<mixed> $selectors
     * @return list<string>
     */
    private static function normalizeIgnoredSelectors(array $selectors): array
    {
        $converter = new CssSelectorConverter();
        $normalized = [];
        foreach ($selectors as $selector) {
            if (!is_string($selector) || trim($selector) === '') {
                throw new InvalidArgumentException('Ignored CSS selectors must be non-empty strings.');
            }

            $selector = trim($selector);
            try {
                $xpath = $converter->toXPath($selector);
            } catch (CssSelectorException $exception) {
                throw new InvalidArgumentException(
                    sprintf('Invalid ignored CSS selector "%s": %s', $selector, $exception->getMessage()),
                    previous: $exception,
                );
            }
            if (self::containsNeverMatchingPredicate($xpath)) {
                throw new InvalidArgumentException(sprintf(
                    'Ignored CSS selector "%s" cannot be evaluated against static HTML.',
                    $selector,
                ));
            }
            $normalized[] = $selector;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Detects Symfony's never-matching XPath predicate without confusing it
     * with the same characters inside an attribute-value string literal.
     */
    private static function containsNeverMatchingPredicate(string $xpath): bool
    {
        $quote = null;
        $length = strlen($xpath);
        for ($index = 0; $index < $length; ++$index) {
            $character = $xpath[$index];
            if ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($character === "'" || $character === '"') {
                $quote = $character;
                continue;
            }
            if (substr($xpath, $index, 3) === '[0]') {
                return true;
            }
        }

        return false;
    }
}
