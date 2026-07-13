<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Tests\Unit\Element;

use NorthFoundry\VoyagerPageMap\Element\AnchorElement;
use NorthFoundry\VoyagerPageMap\Element\ButtonElement;
use NorthFoundry\VoyagerPageMap\Element\FormElement;
use NorthFoundry\VoyagerPageMap\Element\GenericElement;
use NorthFoundry\VoyagerPageMap\Element\HeadingElement;
use NorthFoundry\VoyagerPageMap\Element\ImageElement;
use NorthFoundry\VoyagerPageMap\Element\InputElement;
use NorthFoundry\VoyagerPageMap\Element\OptionElement;
use NorthFoundry\VoyagerPageMap\Element\PageElement;
use NorthFoundry\VoyagerPageMap\Element\SelectElement;
use NorthFoundry\VoyagerPageMap\Element\TextElement;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;
use PHPUnit\Framework\TestCase;

/**
 * Smoke-tests individual element classes without the DOM build pipeline.
 */
final class StandaloneTests extends TestCase
{
    public function testInputElement(): void
    {
        self::assertStringContainsString('?fill', (new InputElement(new ElementReference(1), null))->toText());
    }

    public function testButtonElement(): void
    {
        self::assertSame('@e1 button "Go" [type=submit] {?click, ?focus}', (new ButtonElement(new ElementReference(1), 'Go'))->toText());
    }

    public function testAnchorElement(): void
    {
        self::assertSame(
            '@e1 a "Go" -> / {?click, ?open}',
            (new AnchorElement(new ElementReference(1), 'Go', ['href' => '/']))->toText(),
        );
    }

    public function testAnchorDestinationIsQuotedOnlyWhenNeededAndHrefCanBeRequested(): void
    {
        $anchor = new AnchorElement(
            reference: new ElementReference(1),
            name: 'Go',
            rawAttributes: ['href' => '/path with spaces'],
            includedSourceAttributes: ['href'],
        );

        self::assertSame(
            '@e1 a "Go" -> "/path with spaces" [href="/path with spaces"] {?click, ?open}',
            $anchor->toText(),
        );
    }

    public function testFormElement(): void
    {
        self::assertSame('@e1 form', (new FormElement(new ElementReference(1)))->toText());
    }

    public function testHeadingElement(): void
    {
        self::assertSame('@e1 h3 "Title"', (new HeadingElement(new ElementReference(1), 'Title', sourceTag: 'h3'))->toText());
    }

    public function testSelectElement(): void
    {
        self::assertStringContainsString('?select', (new SelectElement(new ElementReference(1)))->toText());
    }

    public function testOptionElement(): void
    {
        self::assertSame('@e1 option "One"', (new OptionElement(new ElementReference(1), 'One'))->toText());
    }

    public function testImageElement(): void
    {
        self::assertSame('@e1 img "Logo" [alt=Logo]', (new ImageElement(new ElementReference(1), 'Logo', ['alt' => 'Logo']))->toText());
    }

    public function testGenericListElement(): void
    {
        self::assertSame('@e1 ul', (new GenericElement(new ElementReference(1), sourceTag: 'ul'))->toText());
    }

    public function testGenericTableElement(): void
    {
        self::assertSame('@e1 table', (new GenericElement(new ElementReference(1), sourceTag: 'table'))->toText());
    }

    public function testGenericElement(): void
    {
        self::assertStringContainsString('?click', (new GenericElement(new ElementReference(1), 'Go', ['role' => 'button'], sourceTag: 'div'))->toText());
    }

    public function testGenericElementWithHrefUsesTheNavigationGrammar(): void
    {
        $element = new GenericElement(new ElementReference(1), 'Map', ['href' => '/map'], sourceTag: 'area');

        self::assertSame('@e1 area "Map" -> /map {?click, ?open, ?focus}', $element->toText());
    }

    public function testTextElement(): void
    {
        self::assertSame('@e1 text "Note"', (new TextElement(new ElementReference(1), 'Note'))->toText());
    }

    public function testPageElement(): void
    {
        self::assertSame('@e1 page', (new PageElement(new ElementReference(1)))->toText());
    }
}
