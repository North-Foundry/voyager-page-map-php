<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Serialization;

/**
 * Escapes VPM names and property values into deterministic text-safe forms.
 */
final class TextEscaper
{
    /**
     * Quotes a human-readable value and escapes control characters.
     */
    public static function quoted(string $value): string
    {
        return '"' . str_replace(['\\', '"', "\n", "\r", "\t"], ['\\\\', '\\"', '\\n', '\\r', '\\t'], $value) . '"';
    }

    /**
     * Uses a compact token when safe, otherwise returns the quoted value.
     */
    public static function attribute(?string $value): string
    {
        return $value === null ? '' : (preg_match('/^[A-Za-z0-9_-]+$/u', $value) === 1 ? $value : self::quoted($value));
    }

    /**
     * Keeps a grammar value compact unless whitespace or delimiters require
     * an explicitly quoted string.
     */
    public static function token(string $value): string
    {
        return preg_match('/^[^\s"\\\\\[\]{}]+$/u', $value) === 1 ? $value : self::quoted($value);
    }
}
