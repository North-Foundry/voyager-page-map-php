<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Tests\Unit;

use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocument;
use NorthFoundry\VoyagerPageMap\Model\ElementSelectorType;
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
        $target = $pageMap->ref('e2');
        self::assertNotNull($target);
        self::assertNotNull($target->selector);
        self::assertSame(ElementSelectorType::Css, $target->selector->type);
        self::assertSame('html > body > button', $target->cssSelector());
        self::assertNull($target->xpathSelector());
        self::assertSame($target, $pageMap->ref('@E2'));
        self::assertTrue($pageMap->hasRef('e2'));
        self::assertTrue($pageMap->hasRef('@E2'));
        self::assertNull($pageMap->findByReference('@e3'));
        self::assertFalse($pageMap->hasRef('e3'));
        self::assertNull($pageMap->ref('e0'));
        self::assertNull($pageMap->ref('invalid'));
        self::assertFalse($pageMap->hasRef('invalid'));
    }

    public function testCompactReferenceExposesXPathForTextNodes(): void
    {
        $pageMap = VoyagerPageMap::fromHtml('<main>Before <button>Go</button></main>');

        $target = $pageMap->ref('e3');
        self::assertNotNull($target);
        self::assertNull($target->cssSelector());
        self::assertSame('/html[1]/body[1]/main[1]/text()[1]', $target->xpathSelector());
    }
}
