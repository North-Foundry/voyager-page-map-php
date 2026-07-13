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
use NorthFoundry\VoyagerPageMap\Contract\VoyagerPageMapTextSerializableInterface;
use NorthFoundry\VoyagerPageMap\Model\ElementReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Verifies canonical VPM lines emitted by each representative element type.
 */
final class ElementSerializationTest extends TestCase
{
    #[DataProvider('elements')]
    public function testElementSerialization(VoyagerPageMapTextSerializableInterface $element, string $expected): void
    {
        self::assertSame($expected, $element->toText());
    }

    /**
     * @return array<string, array{VoyagerPageMapTextSerializableInterface, string}>
     */
    public static function elements(): array
    {
        return [
            'input' => [new InputElement(new ElementReference(5), 'Email', ['type' => 'email', 'required' => '']), '@e5 input "Email" [type=email, required, empty] {?fill, ?clear, ?focus}'],
            'button' => [new ButtonElement(new ElementReference(1), 'Save'), '@e1 button "Save" [type=submit] {?click, ?focus}'],
            'anchor' => [new AnchorElement(new ElementReference(2), 'Home', ['href' => '/', 'title' => 'Homepage']), '@e2 a "Home" -> / [title=Homepage] {?click, ?open}'],
            'form' => [new FormElement(new ElementReference(3), 'Login', ['method' => 'post']), '@e3 form "Login" [method=post]'],
            'heading' => [new HeadingElement(new ElementReference(4), 'Title', sourceTag: 'h2'), '@e4 h2 "Title"'],
            'select' => [new SelectElement(new ElementReference(6), 'Country', ['name' => 'country']), '@e6 select "Country" [name=country] {?select, ?focus}'],
            'option' => [new OptionElement(new ElementReference(7), 'Italy', ['value' => 'IT', 'selected' => '']), '@e7 option "Italy" [value=IT, selected]'],
            'image' => [new ImageElement(new ElementReference(8), 'Logo', ['src' => '/logo.png', 'alt' => 'Logo']), '@e8 img "Logo" [alt=Logo]'],
            'list' => [new GenericElement(new ElementReference(9), sourceTag: 'ol'), '@e9 ol'],
            'table' => [new GenericElement(new ElementReference(10), 'Orders', sourceTag: 'table'), '@e10 table "Orders"'],
            'text' => [new TextElement(new ElementReference(11), 'Information'), '@e11 text "Information"'],
            'page' => [new PageElement(new ElementReference(12), 'Page', ['lang' => 'it', 'url' => 'https://example.com']), '@e12 page "Page" [lang=it]'],
        ];
    }
}
