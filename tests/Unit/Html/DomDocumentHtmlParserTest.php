<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Tests\Unit\Html;

use NorthFoundry\VoyagerPageMap\Configuration\VoyagerPageMapConfiguration;
use NorthFoundry\VoyagerPageMap\Exception\Html\HtmlParsingException;
use NorthFoundry\VoyagerPageMap\Html\DomDocumentHtmlParser;
use NorthFoundry\VoyagerPageMap\Model\ElementSelectorType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the native parser extracts a complete VPM document.
 */
final class DomDocumentHtmlParserTest extends TestCase
{
    #[DataProvider('fixtures')]
    public function testHtmlFixturesRemainStable(string $name, ?string $baseUrl = null): void
    {
        $html = (string) file_get_contents(__DIR__ . '/../../Fixtures/Parser/' . $name . '.html');
        $expected = (string) file_get_contents(__DIR__ . '/../../Fixtures/Parser/' . $name . '.vpm');
        $document = (new DomDocumentHtmlParser())->parse($html, $baseUrl, VoyagerPageMapConfiguration::default());

        self::assertSame($expected, $document->toText());
    }

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function fixtures(): array
    {
        return [
            'login' => ['login', null],
            'ecommerce' => ['ecommerce', 'https://store.example.com/products'],
            'article' => ['article', null],
            'form' => ['form', null],
            'table' => ['table', null],
            'malformed' => ['malformed', null],
        ];
    }

    public function testParseBuildsMetadataPageAndTypedContent(): void
    {
        $document = (new DomDocumentHtmlParser())->parse(
            '<!doctype html><html lang="it"><head><title>Profilo</title></head><body><button>Salva</button></body></html>',
            'https://example.com/profile',
            VoyagerPageMapConfiguration::default(),
        );

        self::assertSame('Profilo', $document->metadata->title);
        self::assertSame('it', $document->metadata->language);
        self::assertSame('https://example.com/profile', $document->metadata->baseUrl);
        self::assertSame(
            "VPM/1\ntitle Profilo\nurl https://example.com/profile\n\n@e1 page [lang=it]\n  @e2 button \"Salva\" [type=submit] {?click, ?focus}\n",
            $document->toText(),
        );
    }

    public function testEmptyHtmlThrowsTheParsingException(): void
    {
        $this->expectException(HtmlParsingException::class);
        $this->expectExceptionMessage('could not be parsed into a usable DOM document');

        (new DomDocumentHtmlParser())->parse('', null, VoyagerPageMapConfiguration::default());
    }

    public function testMalformedHtmlIsRecoveredWithoutLosingUtf8Text(): void
    {
        $document = (new DomDocumentHtmlParser())->parse(
            '<main><h1>Ciao <em>mondo</h1><p>È già pronto',
            null,
            VoyagerPageMapConfiguration::default(),
        );

        self::assertStringContainsString('h1 "Ciao mondo"', $document->toText());
        self::assertStringContainsString('p "È già pronto"', $document->toText());
    }

    public function testIgnoredNodesDoNotConsumeReferencesAndWrappersArePromoted(): void
    {
        $html = <<<'HTML'
            <div>
                <script>ignored()</script>
                <style>.ignored {}</style>
                <noscript>ignored</noscript>
                <template><button>Ignored</button></template>
                <svg><text>Ignored</text></svg>
                <input type="hidden" value="secret">
                <button hidden>Ignored</button>
                <span><button>Keep</button></span>
            </div>
            HTML;

        $document = (new DomDocumentHtmlParser())->parse($html, null, VoyagerPageMapConfiguration::default());

        self::assertSame(
            "VPM/1\n\n@e1 page\n  @e2 button \"Keep\" [type=submit] {?click, ?focus}\n",
            $document->toText(),
        );
        self::assertNull($document->findByReference('@e3'));
    }

    public function testReadableNamePrecedenceAndImageAlternativeText(): void
    {
        $html = <<<'HTML'
            <span id="external">Nome ARIA</span>
            <label for="field">Nome label</label>
            <input id="field" aria-labelledby="external" aria-label="Ignorato" title="Ignorato" placeholder="Ignorato" name="ignored">
            <button><img src="save.svg" alt="Salva"></button>
            HTML;

        $document = (new DomDocumentHtmlParser())->parse($html, null, VoyagerPageMapConfiguration::default());

        self::assertStringContainsString('input "Nome ARIA"', $document->toText());
        self::assertStringContainsString('button "Salva"', $document->toText());
        self::assertStringNotContainsString('input "Nome label"', $document->toText());
    }

    public function testStructuredChildrenLabelsAndTextareaValuesRemainIndependent(): void
    {
        $html = <<<'HTML'
            <ul><li>Leggi <a href="/more">altro</a></li></ul>
            <label>Email <input type="email"></label>
            <label for="bio">Bio</label><textarea id="bio">Testo già presente</textarea>
            HTML;

        $document = (new DomDocumentHtmlParser())->parse($html, null, VoyagerPageMapConfiguration::default());
        $text = $document->toText();

        self::assertStringContainsString("li\n      @e4 text \"Leggi\"\n      @e5 a \"altro\"", $text);
        self::assertStringContainsString('input "Email" [type=email, empty]', $text);
        self::assertSame(1, substr_count($text, '"Email"'));
        self::assertStringContainsString('textarea "Bio" [value="Testo già presente", filled]', $text);
    }

    public function testReferencesKeepUniqueSelectorsForIdsClassesAndTextNodes(): void
    {
        $document = (new DomDocumentHtmlParser())->parse(
            <<<'HTML'
                <body id="app">
                    <main>
                        <div class="panel">
                            <button class="action">One</button>
                            <button class="action">Two</button>
                            <a id="profile" href="/me">Profile</a>
                        </div>
                        Before <button>Go</button> After
                    </main>
                </body>
                HTML,
            null,
            VoyagerPageMapConfiguration::default(),
        );

        $pageSelector = $document->ref('@e1')?->selector;
        self::assertNotNull($pageSelector);
        self::assertSame(ElementSelectorType::Css, $pageSelector->type);
        self::assertSame('#app', $pageSelector->value);
        self::assertSame('#app > main', $document->ref('@e2')?->selector?->value);
        self::assertSame('#app > main > div.panel > button.action:nth-of-type(1)', $document->ref('@e3')?->selector?->value);
        self::assertSame('#app > main > div.panel > button.action:nth-of-type(2)', $document->ref('@e4')?->selector?->value);
        self::assertSame('#profile', $document->ref('@e5')?->selector?->value);
        $textSelector = $document->ref('@e6')?->selector;
        self::assertNotNull($textSelector);
        self::assertSame(ElementSelectorType::XPath, $textSelector->type);
        self::assertSame('/html[1]/body[1]/main[1]/text()[2]', $textSelector->value);
        self::assertSame('#app > main > button', $document->ref('@e7')?->selector?->value);
        self::assertSame('/html[1]/body[1]/main[1]/text()[3]', $document->ref('@e8')?->selector?->value);
        self::assertNull($document->ref('@e9'));
    }

    public function testDuplicateIdsFallBackToStructuralSelectorsAndCssIdentifiersAreEscaped(): void
    {
        $document = (new DomDocumentHtmlParser())->parse(
            '<main><button id="duplicate">One</button><button id="duplicate">Two</button><a id="123:profile" href="/">Profile</a></main>',
            null,
            VoyagerPageMapConfiguration::default(),
        );

        self::assertSame('html > body > main > button:nth-of-type(1)', $document->ref('@e3')?->cssSelector());
        self::assertSame('html > body > main > button:nth-of-type(2)', $document->ref('@e4')?->cssSelector());
        self::assertSame('#\\31 23\\:profile', $document->ref('@e5')?->cssSelector());
    }

    public function testQuotedLabelIdsAreSafeInXPathQueries(): void
    {
        $document = (new DomDocumentHtmlParser())->parse(
            '<label for="owner\'&quot;">Proprietario</label><input id="owner\'&quot;" type="text">',
            null,
            VoyagerPageMapConfiguration::default(),
        );

        self::assertStringContainsString('input "Proprietario"', $document->toText());
    }

    public function testConfigurationControlsHiddenContainersAttributesAndUrls(): void
    {
        $configuration = VoyagerPageMapConfiguration::diagnostic()->withRelativeUrlResolution();
        $document = (new DomDocumentHtmlParser())->parse(
            '<div id="box"><button hidden data-track="save">Save</button><a href="../account">Account</a></div>',
            'https://example.com/shop/products',
            $configuration,
        );
        $text = $document->toText();

        self::assertStringContainsString('div [id=box]', $text);
        self::assertStringContainsString('button "Save" [type=submit, hidden, data-track=save]', $text);
        self::assertStringContainsString('href="../../account"', $text);
    }

    public function testRelativeLinksUseTheTopLevelUrlAsDocumentContext(): void
    {
        $document = (new DomDocumentHtmlParser())->parse(
            '<title>Account</title><a href="../account">Account</a>',
            'https://example.com/shop/products',
            VoyagerPageMapConfiguration::default(),
        );

        self::assertSame(
            "VPM/1\ntitle Account\nurl https://example.com/shop/products\n\n@e1 page\n  @e2 a \"Account\" -> ../account {?click, ?open}\n",
            $document->toText(),
        );
    }

    public function testUrlCompactionUsesEveryPagePathSegment(): void
    {
        $document = (new DomDocumentHtmlParser())->parse(
            <<<'HTML'
                <a href="https://www.gov.uk/browse/benefits#content">Fragment</a>
                <a href="https://www.gov.uk/">Root</a>
                <a href="https://www.gov.uk/browse">Parent</a>
                <a href="https://www.gov.uk/search">Search</a>
                <a href="https://www.gov.uk/browse/benefits/manage">Child</a>
                <a href="https://external.example/path">External</a>
                HTML,
            'https://www.gov.uk/browse/benefits',
            VoyagerPageMapConfiguration::default()->withRelativeUrlResolution(),
        );
        $text = $document->toText();

        self::assertStringContainsString('a "Fragment" -> #content', $text);
        self::assertStringContainsString('a "Root" -> ../../', $text);
        self::assertStringContainsString('a "Parent" -> ../', $text);
        self::assertStringContainsString('a "Search" -> ../../search', $text);
        self::assertStringContainsString('a "Child" -> manage', $text);
        self::assertStringContainsString('a "External" -> https://external.example/path', $text);
    }

    public function testOneParserInstanceCanBeReusedWithoutLeakingState(): void
    {
        $parser = new DomDocumentHtmlParser();
        $resolved = $parser->parse(
            '<a href="../account">Account</a>',
            'https://example.com/shop/products',
            VoyagerPageMapConfiguration::default()->withRelativeUrlResolution(),
        );
        $fresh = $parser->parse('<button>Fresh</button>', null, VoyagerPageMapConfiguration::default());

        self::assertStringContainsString('@e2 a "Account" -> ../../account', $resolved->toText());
        self::assertSame(
            "VPM/1\n\n@e1 page\n  @e2 button \"Fresh\" [type=submit] {?click, ?focus}\n",
            $fresh->toText(),
        );
    }

    public function testLibxmlErrorModeIsRestoredAfterSuccessAndFailure(): void
    {
        $previousMode = libxml_use_internal_errors(false);
        libxml_clear_errors();

        try {
            $parser = new DomDocumentHtmlParser();
            $parser->parse('<p>Valid</p>', null, VoyagerPageMapConfiguration::default());
            self::assertFalse(libxml_use_internal_errors());

            try {
                $parser->parse('', null, VoyagerPageMapConfiguration::default());
                self::fail('Empty HTML should fail parsing.');
            } catch (HtmlParsingException) {
                self::assertFalse(libxml_use_internal_errors());
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousMode);
        }
    }
}
