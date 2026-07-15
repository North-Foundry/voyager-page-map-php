<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Element;

use NorthFoundry\VoyagerPageMap\Model\ImageSourceSet;
use NorthFoundry\VoyagerPageMap\Serialization\SerializationContext;
use NorthFoundry\VoyagerPageMap\Serialization\TextEscaper;

/**
 * Serializes images while retaining accessibility attributes by default.
 */
final class ImageElement extends AbstractVoyagerPageMapElement
{
    public static function contentMode(array $rawAttributes): ElementContentMode
    {
        return ElementContentMode::None;
    }

    protected function serializedTag(): string
    {
        return 'img';
    }

    protected function serializedDestination(SerializationContext $context): ?string
    {
        if (!$this->has('srcset')) {
            $source = $this->value('src');

            return $source === null ? null : ' -> ' . TextEscaper::token($source);
        }

        $resources = [];
        if ($this->has('src')) {
            $resources[] = ['key' => 'src', 'url' => $this->value('src') ?? ''];
        }
        $keys = [];
        foreach (ImageSourceSet::parse($this->value('srcset') ?? '') as $candidate) {
            $key = $candidate['descriptor'] ?? 'srcset';
            $keys[$key] = ($keys[$key] ?? 0) + 1;
            if ($keys[$key] > 1) {
                $key .= '#' . $keys[$key];
            }
            $resources[] = ['key' => $key, 'url' => $candidate['url']];
        }
        if ($resources === []) {
            return ' -> {}';
        }

        $indentation = str_repeat('  ', $context->indentation);
        $lines = [' -> {'];
        foreach ($resources as $resource) {
            $lines[] = $indentation . '  ' . TextEscaper::token($resource['key']) . ' -> ' . TextEscaper::token($resource['url']);
        }
        $lines[] = $indentation . '}';

        return implode("\n", $lines);
    }

    /**
     * Image URLs are already represented by the canonical destination syntax.
     *
     * @return list<string>
     */
    protected function excludedSourceAttributes(): array
    {
        return ['src', 'srcset'];
    }
}
