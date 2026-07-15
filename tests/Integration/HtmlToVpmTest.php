<?php

declare(strict_types=1);

namespace NorthFoundry\VoyagerPageMap\Tests\Integration;

use NorthFoundry\VoyagerPageMap\Configuration\VoyagerPageMapConfiguration;
use NorthFoundry\VoyagerPageMap\VoyagerPageMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Exercises the public facade against end-to-end HTML-to-VPM fixtures.
 */
final class HtmlToVpmTest extends TestCase
{
    #[DataProvider('realWorldPages')]
    public function testRealWorldPageFixture(string $fixture, string $baseUrl, int $minimumReferences): void
    {
        $fixturePath = __DIR__ . '/../Fixtures/Pages/' . $fixture . '/' . $fixture;
        $configuration = VoyagerPageMapConfiguration::agent()->withRelativeUrlResolution();

        $actual = VoyagerPageMap::fromHtml(
            self::readFixture($fixturePath . '.html'),
            $baseUrl,
            $configuration,
        )->toText();

        self::assertSame(self::readFixture($fixturePath . '.vpm'), $actual);
        self::assertGreaterThanOrEqual($minimumReferences, self::countReferences($actual));
        $url = parse_url($baseUrl);
        if ($url === false || !isset($url['scheme'], $url['host'])) {
            throw new RuntimeException('Invalid fixture base URL: ' . $baseUrl);
        }
        $origin = $url['scheme'] . '://' . $url['host'];
        self::assertStringNotContainsString('-> ' . $origin . '/', $actual);
    }

    /**
     * Each source page is downloaded once, trimmed only to stable structural
     * regions, and committed beside its expected VPM output. Tests never use
     * the network and therefore remain deterministic.
     *
     * @return array<string, array{non-empty-string, non-empty-string, positive-int}>
     */
    public static function realWorldPages(): array
    {
        return [
            'Wikipedia article' => ['WikipediaPage', 'https://en.wikipedia.org/wiki/Document_Object_Model', 500],
            'MDN reference page' => ['MdnPage', 'https://developer.mozilla.org/en-US/docs/Web/API/Document_Object_Model', 200],
            'GOV.UK browse page' => ['GovUkBenefitsPage', 'https://www.gov.uk/browse/benefits', 100],
        ];
    }

    #[DataProvider('parserFixtures')]
    public function testParserFixtureThroughThePublicFacade(string $fixture, ?string $baseUrl): void
    {
        $fixturePath = __DIR__ . '/../Fixtures/Parser/' . $fixture;

        self::assertSame(
            self::readFixture($fixturePath . '.vpm'),
            VoyagerPageMap::fromHtml(self::readFixture($fixturePath . '.html'), $baseUrl)->toText(),
        );
    }

    /**
     * @return array<string, array{non-empty-string, non-empty-string|null}>
     */
    public static function parserFixtures(): array
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

    private static function readFixture(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Unable to read integration fixture: ' . $path);
        }

        return $contents;
    }

    private static function countReferences(string $vpm): int
    {
        $count = preg_match_all('/^\s*@e\d+\b/m', $vpm);
        if ($count === false) {
            throw new RuntimeException('Unable to count VPM references.');
        }

        return $count;
    }

    public function testReferencesAreContiguousAndStandaloneSerializationKeepsChildren(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<div><span><button>Save</button></span></div>');
        self::assertSame("VPM/1\n\n@e1 page\n  @e2 button \"Save\" [type=submit] {?click, ?focus}\n", $vpm->toText());
        self::assertSame('@e2 button "Save" [type=submit] {?click, ?focus}', $vpm->findByReference('@e2')?->toText());
        self::assertNull($vpm->findByReference('@e3'));
    }

    public function testHiddenAndAriaHiddenElementsAreIgnoredByDefault(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<main><input type="hidden" value="secret"><button hidden>Hidden</button><button aria-hidden="true">Also hidden</button><button>Keep</button></main>');
        self::assertSame("VPM/1\n\n@e1 page\n  @e2 main\n    @e3 button \"Keep\" [type=submit] {?click, ?focus}\n", $vpm->toText());
        $included = VoyagerPageMap::fromHtml('<button hidden>Keep</button>', configuration: new VoyagerPageMapConfiguration(includeHiddenElements: true));
        self::assertStringContainsString('[type=submit, hidden]', $included->toText());
    }

    public function testEscapingUtf8AndInteractiveGenericElements(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<div role="button" tabindex="0">Apri "Account" 🚀</div>');
        self::assertSame("VPM/1\n\n@e1 page\n  @e2 div \"Apri \\\"Account\\\" 🚀\" [role=button, tabindex=0] {?click, ?focus}\n", $vpm->toText());
    }

    /**
     * Verifies retained tags without dedicated elements keep their original HTML tag.
     */
    public function testGenericElementsPreserveTheirSourceTag(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<details aria-label="Dettagli"></details>');

        self::assertStringContainsString('details "Dettagli"', $vpm->toText());
    }

    /**
     * Verifies DOM XPath queries escape IDs containing both quote characters.
     */
    public function testLabelsWithQuotedIdsResolveToTheAssociatedControl(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<label for="owner\'&quot;">Proprietario</label><input id="owner\'&quot;" type="text">');

        self::assertStringContainsString('input "Proprietario"', $vpm->toText());
    }

    public function testStructuredContentInsideTextElementsIsRetained(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<ul><li>Leggi <a href="/more">altro</a></li></ul>');

        self::assertSame(
            "VPM/1\n\n@e1 page\n  @e2 ul\n    @e3 li\n      @e4 text \"Leggi\"\n      @e5 a \"altro\" -> /more {?click, ?open}\n",
            $vpm->toText(),
        );

        $paragraphs = VoyagerPageMap::fromHtml('<ul><li><p>Uno</p><p>Due</p></li></ul>');
        self::assertStringContainsString("li\n      @e4 p \"Uno\"\n      @e5 p \"Due\"", $paragraphs->toText());
    }

    public function testImageAlternativeTextNamesInteractiveParents(): void
    {
        $link = VoyagerPageMap::fromHtml('<a href="/"><img src="home.svg" alt="Home"></a>');
        $button = VoyagerPageMap::fromHtml('<button><img src="save.svg" alt="Salva"></button>');

        self::assertStringContainsString('a "Home" -> /', $link->toText());
        self::assertStringContainsString('button "Salva"', $button->toText());
    }

    public function testImageResourcesUseDirectAndGroupedDestinationSyntax(): void
    {
        $direct = VoyagerPageMap::fromHtml('<img src="/assets/logo.svg" alt="Logo">');
        $responsive = VoyagerPageMap::fromHtml('<img src="/images/shoe.jpg" srcset="/images/shoe-480.jpg 480w, /images/shoe-960.jpg 960w" alt="Scarpa rossa">');
        $singleCandidate = VoyagerPageMap::fromHtml('<img srcset="/images/banner@2x.jpg 2x" alt="Banner">');
        $resolved = VoyagerPageMap::fromHtml(
            '<img src="/images/shoe.jpg" srcset="/images/shoe-480.jpg 480w, https://cdn.example.com/shoe-960.jpg 960w" alt="Scarpa">',
            'https://example.com/products/shoe',
            VoyagerPageMapConfiguration::agent()->withRelativeUrlResolution(),
        );

        self::assertSame("VPM/1\n\n@e1 page\n  @e2 img \"Logo\" -> /assets/logo.svg [alt=Logo]\n", $direct->toText());
        self::assertSame(
            "VPM/1\n\n@e1 page\n  @e2 img \"Scarpa rossa\" -> {\n    src -> /images/shoe.jpg\n    480w -> /images/shoe-480.jpg\n    960w -> /images/shoe-960.jpg\n  } [alt=\"Scarpa rossa\"]\n",
            $responsive->toText(),
        );
        self::assertSame("VPM/1\n\n@e1 page\n  @e2 img \"Banner\" -> {\n    2x -> /images/banner@2x.jpg\n  } [alt=Banner]\n", $singleCandidate->toText());
        self::assertSame(
            "VPM/1\nurl https://example.com/products/shoe\n\n@e1 page\n  @e2 img \"Scarpa\" -> {\n    src -> ../../images/shoe.jpg\n    480w -> ../../images/shoe-480.jpg\n    960w -> https://cdn.example.com/shoe-960.jpg\n  } [alt=Scarpa]\n",
            $resolved->toText(),
        );
    }

    public function testWrappingLabelIsConsumedWithoutDuplicatingItsText(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<label>Email <input type="email"></label>');

        self::assertSame("VPM/1\n\n@e1 page\n  @e2 input \"Email\" [type=email, empty] {?fill, ?clear, ?focus}\n", $vpm->toText());
    }

    public function testWrappingLabelNamesSelectWithoutRenamingItsOptions(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<label>Paese <select><option>Italia</option></select></label>');

        self::assertStringContainsString("select \"Paese\"", $vpm->toText());
        self::assertStringContainsString("option \"Italia\"", $vpm->toText());
    }

    public function testTextareaContentIsRetainedAsItsStaticValue(): void
    {
        $vpm = VoyagerPageMap::fromHtml('<label for="bio">Bio</label><textarea id="bio">Testo già presente</textarea>');

        self::assertStringContainsString('textarea "Bio" [value="Testo già presente", filled]', $vpm->toText());
    }

    public function testRelativeUrlResolutionFollowsStandardUrlReferences(): void
    {
        $configuration = VoyagerPageMapConfiguration::agent()
            ->withRelativeUrlResolution()
            ->withAttributes(['src']);
        $vpm = VoyagerPageMap::fromHtml(
            '<a href="../account">Account</a><a href="?page=2">Next</a><img src="//cdn.example.com/a.png" alt="A">',
            'https://example.com/shop/products',
            $configuration,
        );

        self::assertStringContainsString('-> ../../account', $vpm->toText());
        self::assertStringContainsString('-> ?page=2', $vpm->toText());
        self::assertStringContainsString('img "A" -> //cdn.example.com/a.png', $vpm->toText());
        self::assertStringNotContainsString('href=', $vpm->toText());

        $withHref = VoyagerPageMap::fromHtml(
            '<a href="/account">Account</a>',
            configuration: VoyagerPageMapConfiguration::agent()->withAttributes(['href']),
        );
        self::assertStringContainsString('a "Account" -> /account [href="/account"]', $withHref->toText());

        $references = VoyagerPageMap::fromHtml(
            '<a href="#details">Details</a><form action="/save"></form>',
            'https://example.com/shop/products?old=1',
            $configuration,
        );
        self::assertStringContainsString('-> #details', $references->toText());
        self::assertStringContainsString('action="../../save"', $references->toText());
    }

    public function testConfigurationProfilesAndFluentMethods(): void
    {
        self::assertInstanceOf(VoyagerPageMapConfiguration::class, VoyagerPageMapConfiguration::default());
        self::assertEquals(VoyagerPageMapConfiguration::agent(), VoyagerPageMapConfiguration::default());
        self::assertEquals(VoyagerPageMapConfiguration::agent(), VoyagerPageMapConfiguration::compact());
        self::assertFalse(VoyagerPageMapConfiguration::agent()->resolveRelativeUrls);
        self::assertTrue(VoyagerPageMapConfiguration::diagnostic()->includeHiddenElements);
        self::assertTrue(VoyagerPageMapConfiguration::diagnostic()->includeGenericContainers);
        self::assertFalse(VoyagerPageMapConfiguration::diagnostic()->resolveRelativeUrls);

        $configuration = (new VoyagerPageMapConfiguration())
            ->withHiddenElements()
            ->withGenericContainers()
            ->withRelativeUrlResolution();

        self::assertTrue($configuration->includeHiddenElements);
        self::assertTrue($configuration->includeGenericContainers);
        self::assertTrue($configuration->resolveRelativeUrls);
        self::assertInstanceOf(VoyagerPageMapConfiguration::class, VoyagerPageMapConfiguration::agent()->withAttributes());

        $diagnostic = VoyagerPageMap::fromHtml('<button id="save" class="primary action" data-tracking="checkout">Save</button>', configuration: VoyagerPageMapConfiguration::diagnostic());
        self::assertStringContainsString('[type=submit, id=save, class="primary action", data-tracking=checkout]', $diagnostic->toText());

        $selectedAttributes = VoyagerPageMap::fromHtml('<button id="save" class="primary" data-tracking="checkout">Save</button>', configuration: VoyagerPageMapConfiguration::agent()->withAttributes(['id', 'data-tracking']));
        self::assertStringContainsString('[type=submit, id=save, data-tracking=checkout]', $selectedAttributes->toText());
        self::assertStringNotContainsString('class=primary', $selectedAttributes->toText());

        $structuralAttributes = VoyagerPageMap::fromHtml('<article id="feature" data-kind="release"><p>New</p></article>', configuration: VoyagerPageMapConfiguration::agent()->withAttributes(['id', 'data-kind']));
        self::assertStringContainsString('@e2 article [id=feature, data-kind=release]', $structuralAttributes->toText());

        $internalLookingAttribute = VoyagerPageMap::fromHtml('<article _tag="source"></article>', configuration: VoyagerPageMapConfiguration::diagnostic());
        self::assertStringContainsString('article [_tag=source]', $internalLookingAttribute->toText());

        $password = VoyagerPageMap::fromHtml('<input type="password" value="secret" data-field="password">', configuration: VoyagerPageMapConfiguration::agent()->withAttributes());
        self::assertStringNotContainsString('value=secret', $password->toText());
        self::assertStringContainsString('data-field=password', $password->toText());

        $text = VoyagerPageMap::fromHtml('<main>Always present</main>', configuration: VoyagerPageMapConfiguration::compact());
        self::assertStringContainsString('text "Always present"', $text->toText());
    }

    public function testReadonlyFieldsBlockEditingActions(): void
    {
        $text = VoyagerPageMap::fromHtml('<input aria-label="Code" readonly>')->toText();

        self::assertStringContainsString('{!fill, !clear, ?focus}', $text);
    }
}
