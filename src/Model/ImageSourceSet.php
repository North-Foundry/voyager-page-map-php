<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Model;

/**
 * Parses the URL candidates exposed by an HTML img srcset attribute.
 */
final class ImageSourceSet
{
    /**
     * @return list<array{url: string, descriptor: string|null}>
     */
    public static function parse(string $value): array
    {
        $candidates = [];
        $length = strlen($value);
        $position = 0;

        while ($position < $length) {
            while ($position < $length && (ctype_space($value[$position]) || $value[$position] === ',')) {
                ++$position;
            }
            if ($position >= $length) {
                break;
            }

            $urlStart = $position;
            while ($position < $length && !ctype_space($value[$position])) {
                ++$position;
            }
            $rawUrl = substr($value, $urlStart, $position - $urlStart);
            $endsCandidate = str_ends_with($rawUrl, ',');
            $url = rtrim($rawUrl, ',');
            if ($url === '') {
                continue;
            }

            if ($endsCandidate) {
                $candidates[] = ['url' => $url, 'descriptor' => null];

                continue;
            }

            $descriptors = [];
            while ($position < $length) {
                while ($position < $length && ctype_space($value[$position])) {
                    ++$position;
                }
                if ($position >= $length) {
                    break;
                }
                if ($value[$position] === ',') {
                    ++$position;
                    break;
                }

                $descriptorStart = $position;
                while ($position < $length && !ctype_space($value[$position]) && $value[$position] !== ',') {
                    ++$position;
                }
                $descriptors[] = substr($value, $descriptorStart, $position - $descriptorStart);
                if ($position < $length && $value[$position] === ',') {
                    ++$position;
                    break;
                }
            }

            $candidates[] = [
                'url' => $url,
                'descriptor' => $descriptors === [] ? null : implode(' ', $descriptors),
            ];
        }

        return $candidates;
    }

    /**
     * @param list<array{url: string, descriptor: string|null}> $candidates
     */
    public static function serialize(array $candidates): string
    {
        return implode(', ', array_map(
            static fn(array $candidate): string => $candidate['url'] . ($candidate['descriptor'] === null ? '' : ' ' . $candidate['descriptor']),
            $candidates,
        ));
    }
}
