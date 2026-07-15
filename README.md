# Voyager Page Map

[![CI](https://github.com/North-Foundry/voyager-page-map-php/actions/workflows/ci.yml/badge.svg)](https://github.com/North-Foundry/voyager-page-map-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/north-foundry/voyager-page-map-php.svg)](https://packagist.org/packages/north-foundry/voyager-page-map-php)
[![Total Downloads](https://img.shields.io/packagist/dt/north-foundry/voyager-page-map-php.svg)](https://packagist.org/packages/north-foundry/voyager-page-map-php)
[![PHP Version](https://img.shields.io/packagist/dependency-v/north-foundry/voyager-page-map-php/php.svg)](https://packagist.org/packages/north-foundry/voyager-page-map-php)
[![License](https://img.shields.io/packagist/l/north-foundry/voyager-page-map-php.svg)](LICENSE)

**Turn static HTML into deterministic page context for LLMs.**

Voyager Page Map (VPM) is a PHP library that parses static HTML into `VPM/1`: a semantic, HTML-first text representation designed to be included in an LLM prompt.

```text
static HTML -> Voyager Page Map -> VPM/1 text -> your LLM or agent
```

VPM preserves the information needed to understand and address a page—its structure, readable names, controls, declared state, link destinations, and potential actions—while filtering implementation noise such as scripts, styles, metadata, and irrelevant wrappers.

Every retained node receives a document-local reference such as `@e5`. That reference can also be resolved to a CSS or XPath selector for the source DOM.

Voyager Page Map is the context layer between your HTML source and your LLM. It does not fetch pages, execute JavaScript, control a browser, or call a language model.

## Why VPM?

Raw HTML contains presentation markup and implementation details that are often irrelevant to a language model. VPM produces a focused, predictable representation of page meaning and interaction without replacing familiar HTML semantics with a separate taxonomy.

Typical uses include:

- giving an LLM page context for extraction, classification, summarization, or question answering;
- grounding model output in explicit elements such as `@e5`;
- exposing controls, destinations, and potential actions so an agent can plan an interaction that another runtime will execute;
- creating deterministic semantic snapshots for tests or page-change analysis.

## Requirements

- PHP 8.3 or later
- DOM extension
- libxml extension

## Installation

```bash
composer require north-foundry/voyager-page-map-php
```

## Quick start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use NorthFoundry\VoyagerPageMap\VoyagerPageMap;

$html = <<<'HTML'
<!doctype html>
<html lang="it">
<head>
    <title>Login</title>
</head>
<body>
    <main>
        <h1>Accedi</h1>
        <form aria-label="Login">
            <label for="email">Email</label>
            <input id="email" type="email" required>
            <button type="submit">Accedi</button>
            <a href="/reset-password">Password dimenticata</a>
        </form>
    </main>
</body>
</html>
HTML;

$pageMap = VoyagerPageMap::fromHtml(
    $html,
    baseUrl: 'https://example.com/login',
);

echo $pageMap->toText();
```

Output:

```text
VPM/1
title Login
url https://example.com/login

@e1 page [lang=it]
  @e2 main
    @e3 h1 "Accedi"
    @e4 form "Login"
      @e5 input "Email" [type=email, required, empty] {?fill, ?clear, ?focus}
      @e6 button "Accedi" [type=submit] {?click, ?focus}
      @e7 a "Password dimenticata" -> /reset-password {?click, ?open}
```

## Give the page map to an LLM

VPM is model-provider agnostic. Include the serialized page map in the prompt sent through the client of your choice:

```php
$pageContext = $pageMap->toText();

$prompt = <<<PROMPT
You are given a semantic map of a web page.

Identify the email field.
Return only its @e reference.

$pageContext
PROMPT;

// Pass $prompt to the LLM client or prompt pipeline of your choice.
```

If the model returns `@e5`, use it to inspect the corresponding VPM element or recover its source-DOM selector:

```php
$choice = '@e5';

$element = $pageMap->findByReference($choice);
$reference = $pageMap->ref($choice);

echo $element?->toText();
// @e5 input "Email" [type=email, required, empty] {?fill, ?clear, ?focus}

$selector = $reference?->cssSelector()
    ?? $reference?->xpathSelector();

echo $selector;
// #email
```

Selectors address the DOM from which that VPM document was created. Voyager Page Map does not execute the selected action.

## Reading VPM/1

```text
@e5 input "Email" [type=email, required, empty] {?fill, ?clear, ?focus}
```

- `@e5` is the element reference.
- `input` preserves the native HTML tag.
- `"Email"` is the resolved readable name.
- `[...]` contains semantic properties and static state.
- `{...}` contains actions inferred from the markup.
- `?fill` means that `fill` is possible but has not been runtime-verified; `!action` marks an action blocked by declared state.
- `-> /path` identifies a navigation destination.

References are contiguous and deterministic for the same HTML and configuration. They are local to one VPM document and should not be treated as persistent IDs across page changes.

The complete grammar and retention rules are covered in the [project documentation](docs/).

## Configuration

Configurations are immutable and can be reused:

```php
use NorthFoundry\VoyagerPageMap\Configuration\VoyagerPageMapConfiguration;

$configuration = VoyagerPageMapConfiguration::agent()
    ->withRelativeUrlResolution()
    ->withAttributes(['id', 'data-tracking'])
    ->withIgnoredSelectors(['.cookie-banner', '[data-testid="advertisement"]']);

$pageMap = VoyagerPageMap::fromHtml(
    $html,
    baseUrl: 'https://example.com/login',
    configuration: $configuration,
);
```

Available profiles:

- `default()` and `agent()` produce the standard LLM-oriented representation.
- `compact()` currently produces the same output as `agent()`.
- `diagnostic()` retains declared hidden elements, generic containers, and all source attributes.

Available modifiers:

- `withHiddenElements()` retains elements declared with `hidden` or `aria-hidden`; hidden inputs remain excluded.
- `withGenericContainers()` retains otherwise-promoted `div` and `span` elements.
- `withAttributes()` includes all or selected source attributes.
- `withRelativeUrlResolution()` compacts same-origin URLs relative to `baseUrl`.
- `withIgnoredSelectors()` excludes every subtree matching one of the supplied CSS selectors.

Ignored selectors are evaluated against the parsed source DOM before references are allocated, so removed nodes do not leave gaps in `@eN` numbering. A matching element and all its descendants are excluded. Invalid selectors and browser-state selectors that cannot be evaluated against static HTML are rejected with an `InvalidArgumentException`.

`VPM`, `VPMConfiguration`, and `VPMDocument` are shorter aliases for the canonical class names.

## Scope and limitations

Voyager Page Map analyzes only the supplied static HTML. It does not:

- download or navigate to a URL;
- execute JavaScript or inspect client-side state;
- calculate layout, rendered visibility, occlusion, or pointer reachability;
- verify whether an inferred action actually succeeds;
- take screenshots or control a browser;
- send data to an LLM.

HTML obtained through another HTTP or browser layer can still be passed to `fromHtml()`.

VPM is not a sanitizer or redaction layer. Page text, textarea content, non-password input values, and explicitly selected attributes may appear in the output. Review or redact sensitive content before sending VPM to a third-party model provider.

## Documentation

The VPM/1 specification and LLM integration guidance are available in the [documentation directory](docs/).

## Development

```bash
composer test
composer analyse
composer format:check
composer check
```

## License

Voyager Page Map is released under the [MIT License](LICENSE).
