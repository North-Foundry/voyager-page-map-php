<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Contract\VoyagerPageMapElementInterface;
use NorthFoundry\VoyagerPageMap\Model\ActionAvailability;
use NorthFoundry\VoyagerPageMap\Model\ElementActionCollection;
use NorthFoundry\VoyagerPageMap\Model\ElementAttributeCollection;
use NorthFoundry\VoyagerPageMap\Model\ElementCollection;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;
use NorthFoundry\VoyagerPageMap\Serialization\SerializationContext;
use NorthFoundry\VoyagerPageMap\Serialization\TextEscaper;

/**
 * Base implementation for VPM nodes: it stores common model data, owns child
 * ordering, and applies the shared VPM/1 line and subtree serialization rules.
 */
abstract class AbstractVoyagerPageMapElement implements VoyagerPageMapElementInterface
{
    private readonly ElementCollection $children;

    /**
     * @param array<string, string> $rawAttributes Source attributes normalized by name.
     * @param bool|list<string> $includedSourceAttributes Source attributes to append after canonical VPM properties.
     * @param list<VoyagerPageMapElementInterface> $children Retained VPM children in source order.
     */
    public function __construct(
        private readonly ElementReference $reference,
        private readonly ?string $name = null,
        protected readonly array $rawAttributes = [],
        private readonly ?string $sourceTag = null,
        private readonly bool|array $includedSourceAttributes = false,
        private readonly ?string $content = null,
        array $children = [],
    ) {
        $this->children = new ElementCollection($children);
    }

    /**
     * Declares how this element consumes or retains its source DOM content.
     *
     * @param array<string, string> $rawAttributes
     */
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::Children;
    }

    /**
     * Returns this node's stable document reference.
     */
    public function reference(): ElementReference
    {
        return $this->reference;
    }

    /**
     * Returns the tag that this concrete element serializes.
     */
    public function tag(): string
    {
        return $this->serializedTag();
    }

    /**
     * Returns the resolved static accessible name, when present.
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Returns child nodes in their retained DOM order.
     */
    public function children(): ElementCollection
    {
        return $this->children;
    }

    /**
     * Serializes this node and, unless it is a leaf, its complete subtree.
     */
    final public function toText(?SerializationContext $context = null): string
    {
        $context ??= new SerializationContext();
        $line = str_repeat('  ', $context->indentation) . $this->reference . ' ' . $this->serializedTag();
        if ($this->name !== null && $this->name !== '') {
            $line .= ' ' . TextEscaper::quoted($this->name);
        }
        $destination = $this->destination();
        if ($destination !== null) {
            $line .= ' -> ' . TextEscaper::token($destination);
        }
        $attributes = $this->serializedAttributes();
        if ($attributes->count() > 0) {
            $parts = [];
            foreach ($attributes as $attribute) {
                $parts[] = $attribute->name . ($attribute->value === null ? '' : '=' . TextEscaper::attribute($attribute->value));
            }
            $line .= ' [' . implode(', ', $parts) . ']';
        }
        $actions = $this->serializedActions();
        if ($actions->count() > 0) {
            $parts = [];
            foreach ($actions as $action) {
                $prefix = match ($action->availability) {
                    ActionAvailability::Available => '', ActionAvailability::Unverified => '?', ActionAvailability::Blocked => '!', ActionAvailability::Unsupported => '-',
                };
                $parts[] = $prefix . $action->name;
            }
            $line .= ' {' . implode(', ', $parts) . '}';
        }
        if ($this->children->count() === 0) {
            return $line;
        }
        $lines = [$line];
        foreach ($this->children as $child) {
            $lines[] = $child->toText($context->child());
        }
        return implode("\n", $lines);
    }

    abstract protected function serializedTag(): string;

    protected function serializedAttributes(): ElementAttributeCollection
    {
        return $this->appendSourceAttributes($this->generalAttributes());
    }

    protected function serializedActions(): ElementActionCollection
    {
        return new ElementActionCollection();
    }

    protected function has(string $name): bool
    {
        return array_key_exists($name, $this->rawAttributes);
    }

    protected function value(string $name): ?string
    {
        return $this->rawAttributes[$name] ?? null;
    }

    protected function sourceTag(): ?string
    {
        return $this->sourceTag;
    }

    protected function content(): ?string
    {
        return $this->content;
    }

    /**
     * Returns the static navigation destination exposed by an href, including
     * an empty href that intentionally targets the current document.
     */
    protected function destination(): ?string
    {
        return $this->has('href') ? $this->value('href') : null;
    }

    protected function disabled(): bool
    {
        return $this->has('disabled') || $this->value('aria-disabled') === 'true';
    }

    protected function generalAttributes(): ElementAttributeCollection
    {
        $result = new ElementAttributeCollection();
        foreach (['role', 'alt', 'title', 'lang', 'tabindex'] as $name) {
            if ($this->has($name)) {
                $result->add($name, $this->value($name));
            }
        }
        if ($this->has('contenteditable')) {
            $result->add('contenteditable', $this->value('contenteditable') ?: 'true');
        }
        if ($this->has('hidden')) {
            $result->add('hidden');
        }
        if ($this->value('aria-expanded') === 'true') {
            $result->add('expanded');
        }
        if ($this->value('aria-expanded') === 'false') {
            $result->add('collapsed');
        }
        return $result;
    }

    protected function appendGeneral(ElementAttributeCollection $attributes): ElementAttributeCollection
    {
        foreach ($this->generalAttributes() as $attribute) {
            $attributes->add($attribute->name, $attribute->value);
        }

        return $this->appendSourceAttributes($attributes);
    }

    /**
     * Appends selected source attributes without duplicating element-specific VPM properties.
     */
    private function appendSourceAttributes(ElementAttributeCollection $attributes): ElementAttributeCollection
    {
        if ($this->includedSourceAttributes !== false) {
            $allowedAttributes = $this->includedSourceAttributes === true ? null : array_flip($this->includedSourceAttributes);
            $emitted = [];
            foreach ($attributes as $attribute) {
                $emitted[$attribute->name] = true;
            }
            foreach ($this->rawAttributes as $name => $value) {
                if (isset($emitted[$name]) || $this->isProtectedAttribute($name) || ($allowedAttributes !== null && !isset($allowedAttributes[$name]))) {
                    continue;
                }
                $attributes->add($name, $value);
            }
        }

        return $attributes;
    }

    /**
     * Prevents diagnostic attribute output from disclosing password values.
     */
    private function isProtectedAttribute(string $name): bool
    {
        return $this->serializedTag() === 'input'
            && strtolower($this->value('type') ?? 'text') === 'password'
            && $name === 'value';
    }

    protected function focusAction(ElementActionCollection $actions): void
    {
        $actions->add('focus', $this->disabled() ? ActionAvailability::Blocked : ActionAvailability::Unverified);
    }
}
