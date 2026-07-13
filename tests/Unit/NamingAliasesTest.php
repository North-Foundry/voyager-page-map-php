<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Tests\Unit;

use NorthFoundry\VoyagerPageMap\Configuration\VPMConfiguration;
use NorthFoundry\VoyagerPageMap\Configuration\VoyagerPageMapConfiguration;
use NorthFoundry\VoyagerPageMap\Contract\VPMElementInterface;
use NorthFoundry\VoyagerPageMap\Contract\VPMTextSerializableInterface;
use NorthFoundry\VoyagerPageMap\Contract\VoyagerPageMapElementInterface;
use NorthFoundry\VoyagerPageMap\Contract\VoyagerPageMapTextSerializableInterface;
use NorthFoundry\VoyagerPageMap\Document\VPMDocument;
use NorthFoundry\VoyagerPageMap\Document\VPMDocumentMetadata;
use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocument;
use NorthFoundry\VoyagerPageMap\Document\VoyagerPageMapDocumentMetadata;
use NorthFoundry\VoyagerPageMap\Element\AbstractVPMElement;
use NorthFoundry\VoyagerPageMap\Element\AbstractVoyagerPageMapElement;
use NorthFoundry\VoyagerPageMap\Element\InputElement;
use NorthFoundry\VoyagerPageMap\Element\PageElement;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;
use NorthFoundry\VoyagerPageMap\VPM;
use NorthFoundry\VoyagerPageMap\VoyagerPageMap;
use PHPUnit\Framework\TestCase;

/**
 * Ensures every public VPM shorthand is a true alias of its canonical name.
 */
final class NamingAliasesTest extends TestCase
{
    public function testConfigurationAliasUsesTheCanonicalType(): void
    {
        $canonical = VoyagerPageMapConfiguration::default();
        $short = VPMConfiguration::default();

        self::assertInstanceOf(VPMConfiguration::class, $canonical);
        self::assertInstanceOf(VoyagerPageMapConfiguration::class, $short);
        self::assertSame(VoyagerPageMapConfiguration::class, $short::class);
    }

    public function testDocumentAliasesUseTheCanonicalTypes(): void
    {
        $metadata = new VoyagerPageMapDocumentMetadata(null, null, null);
        $document = new VoyagerPageMapDocument(new PageElement(new ElementReference(1)), $metadata);

        self::assertInstanceOf(VPMDocumentMetadata::class, $metadata);
        self::assertInstanceOf(VPMDocument::class, $document);
        self::assertSame(VoyagerPageMapDocumentMetadata::class, $metadata::class);
        self::assertSame(VoyagerPageMapDocument::class, $document::class);
    }

    public function testElementAliasesUseTheCanonicalContractsAndBaseClass(): void
    {
        $element = new InputElement(new ElementReference(1));

        self::assertInstanceOf(VoyagerPageMapElementInterface::class, $element);
        self::assertInstanceOf(VPMElementInterface::class, $element);
        self::assertInstanceOf(VoyagerPageMapTextSerializableInterface::class, $element);
        self::assertInstanceOf(VPMTextSerializableInterface::class, $element);
        self::assertInstanceOf(AbstractVoyagerPageMapElement::class, $element);
        self::assertInstanceOf(AbstractVPMElement::class, $element);
    }

    public function testFacadeAliasUsesTheCanonicalType(): void
    {
        $canonical = VoyagerPageMap::fromHtml('<p>Canonical</p>');
        $short = VPM::fromHtml('<p>Short</p>');

        self::assertInstanceOf(VPM::class, $canonical);
        self::assertInstanceOf(VoyagerPageMap::class, $short);
        self::assertSame(VoyagerPageMap::class, $short::class);
    }
}
