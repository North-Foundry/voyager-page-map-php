<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Tests\Unit;

use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocument;
use NorthFoundry\VoyagerPageMap\VoyagerPageMap;
use PHPUnit\Framework\TestCase;

/**
 * Covers the small public facade independently from parser implementation details.
 */
final class VoyagerPageMapTest extends TestCase
{
    public function testFacadeBuildsAndExposesTheDocument(): void
    {
        $pageMap = VoyagerPageMap::fromHtml(
            '<html><head><title>Example</title></head><body><button>Save</button></body></html>',
            'https://example.com/path',
        );

        self::assertInstanceOf(VoyagerPageMapDocument::class, $pageMap->document());
        self::assertSame($pageMap->document()->toText(), $pageMap->toText());
        self::assertSame(
            '@e2 button "Save" [type=submit] {?click, ?focus}',
            $pageMap->findByReference('@e2')?->toText(),
        );
        self::assertNull($pageMap->findByReference('@e3'));
    }
}
