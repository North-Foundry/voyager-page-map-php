<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Tests\Unit\Document;

use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocument;
use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocumentMetadata;
use NorthFoundry\VoyagerPageMap\Element\PageElement;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;
use PHPUnit\Framework\TestCase;

/**
 * Verifies document context is serialized before the element tree.
 */
final class VoyagerPageMapDocumentTest extends TestCase
{
    public function testTitleAndUrlAreTopLevelContextWithoutPageDuplication(): void
    {
        $document = new VoyagerPageMapDocument(
            new PageElement(new ElementReference(1), rawAttributes: ['lang' => 'it']),
            new VoyagerPageMapDocumentMetadata('A title with spaces', 'it', 'https://example.com/base/path'),
        );

        self::assertSame(
            "VPM/1\ntitle \"A title with spaces\"\nurl https://example.com/base/path\n\n@e1 page [lang=it]\n",
            $document->toText(),
        );
    }

    public function testSingleTokenTitleIsUnquotedAndMissingUrlIsOmitted(): void
    {
        $document = new VoyagerPageMapDocument(
            new PageElement(new ElementReference(1)),
            new VoyagerPageMapDocumentMetadata('Home', null, null),
        );

        self::assertSame("VPM/1\ntitle Home\n\n@e1 page\n", $document->toText());
    }
}
