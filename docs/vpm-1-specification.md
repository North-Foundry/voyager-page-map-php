# VPM/1 Language Specification

[VPM/1 specification](vpm-1-specification.md) · [LLM integration](llm-integration.md)

This document defines the `VPM/1` text format emitted by Voyager Page Map. It specifies document syntax, ordering, escaping, references, properties, actions, and serialization invariants. It intentionally does not define how HTML is interpreted; that is producer behavior outside the language specification.

## Status and terminology

`VPM/1` is the only format version implemented by this repository.

The key words **MUST**, **MUST NOT**, **SHOULD**, **SHOULD NOT**, and **MAY** describe normative requirements. A serializer conforms to this specification when it produces documents satisfying the required grammar and invariants. A consumer conforms when it can interpret every required construct described here.

The version marker is the format compatibility boundary. A change that invalidates existing VPM/1 consumers requires a new version marker rather than a silent grammar change.

## Document structure

A VPM/1 document contains:

1. the literal version marker `VPM/1`;
2. optional document metadata, in canonical order;
3. one blank line;
4. exactly one synthetic `page` root and its descendants;
5. exactly one final line feed.

```text
VPM/1
title "Account settings"
url https://example.com/settings

@e1 page [lang=en]
  @e2 main
    @e3 h1 "Account settings"
```

Serializers MUST use the line-feed character (`LF`, `U+000A`) as the line separator. They MUST NOT emit an additional blank line after the root subtree.

### Metadata

The supported document metadata fields are:

| Field | Source meaning | Position |
|---|---|---:|
| `title` | Human-readable document title | First, when present |
| `url` | Base URL supplied for this document | After `title`, when present |

Metadata fields are omitted when their value is unavailable or empty. `title` and `url` values use the token rules defined below. The HTML language is not a document header field; when present, it is serialized as `lang` on the synthetic `page` root.

## Element line grammar

Each element occupies one line, except an image with a `srcset`, whose resource set spans multiple lines. Fields retain this order:

```text
<indent><reference> <tag> <name?> <destination?> <properties?> <actions?>
```

An informal grammar is:

```text
element      = indent, reference, SP, tag,
               [SP, quoted-name],
               [destination],
               [SP, properties],
               [SP, actions] ;

destination  = SP, "->", SP, token
             | SP, "->", SP, resource-set ;
resource-set = "{", LF,
               {resource-line, LF},
               indent, "}" ;
resource-line = indent, "  ", resource-key, SP, "->", SP, token ;
resource-key = token ;

reference    = "@e", positive-integer ;
properties   = "[", property, {", ", property}, "]" ;
property     = property-name, ["=", attribute-value] ;
actions      = "{", action, {", ", action}, "}" ;
action       = [action-marker], action-name ;
action-marker = "?" | "!" | "-" ;
indent       = {"  "} ;
SP           = " " ;
```

Optional properties and actions MUST be omitted completely when empty. A serializer MUST NOT leave empty property or action delimiters such as `[]` or `{}`. An empty resource set is permitted only when the source declares an empty `srcset`.

## References

Every retained node, including the synthetic page root and retained text nodes, has a reference in the form `@e<number>`.

References MUST satisfy all of the following:

- numbering starts at `@e1`;
- numbers are positive base-10 integers without leading zeroes;
- `@e1` identifies the synthetic page root;
- references are contiguous after filtering;
- references follow the final VPM tree in depth-first preorder;
- each reference occurs exactly once in a document.

References identify nodes only inside the VPM document that contains them. They are deterministic for the same HTML input, parser behavior, and configuration, but they are not persistent identifiers across page changes.

## Hierarchy and ordering

The `page` root has no indentation. Each descendant level adds exactly two ASCII spaces.

```text
@e1 page
  @e2 main
    @e3 form "Search"
      @e4 input "Query" [type=search, empty] {?fill, ?clear, ?focus}
```

Children MUST appear immediately after their parent subtree begins and MUST preserve their final source order. Indentation is the only structural delimiter; there are no closing element lines.

The ordering of fields on an element line is fixed:

1. reference;
2. tag;
3. readable name;
4. destination;
5. properties;
6. actions.

Properties and actions also have deterministic element-specific order. Canonical semantic properties precede explicitly included source attributes.

## Tags

VPM is HTML-first. Retained HTML elements normally keep their lowercase native tag, for example `input`, `a`, `h2`, `table`, `td`, or `details`.

Two synthetic tags are defined:

- `page` represents the document root;
- `text` represents a retained informative DOM text node that does not belong to a named text-bearing leaf.

The format does not translate native elements into a separate accessibility-role taxonomy. A source `role`, when retained, is represented as a property.

## Readable names

When an element has a readable name, the name immediately follows the tag and is always quoted:

```text
@e3 button "Save changes" [type=submit] {?click, ?focus}
```

Quoted values begin and end with `"`. The following characters MUST be escaped:

| Source character | Serialized sequence |
|---|---|
| Backslash | `\\` |
| Double quote | `\"` |
| Line feed | `\n` |
| Carriage return | `\r` |
| Tab | `\t` |

Other Unicode characters are preserved. For example:

```text
@e2 div "Open \"Account\" 🚀" [role=button, tabindex=0] {?click, ?focus}
```

The language format does not define how a readable name is discovered. Each producer defines its own resolution order.

## Tokens

Metadata values and navigation destinations use compact tokens when the value contains none of the following:

- whitespace;
- double quotes;
- backslashes;
- square brackets;
- curly braces.

```text
title Login
url https://example.com/login
@e2 a "Account" -> /account {?click, ?open}
```

When a token contains a reserved character, or when the value is empty, it uses the quoted-string representation:

```text
title "Account settings"
@e2 a "Current page" -> "" {?click, ?open}
```

## Navigation destinations

An element with a serialized navigation destination places it after the readable name using `->`:

```text
@e4 a "Privacy policy" -> /legal/privacy {?click, ?open}
```

The destination is distinct from the property block. An `href` is therefore not repeated as a source property unless source-attribute configuration explicitly includes it.

VPM/1 permits absolute URLs, protocol-relative URLs, root-relative paths, relative paths, query references, fragments, and the empty current-document reference. URL normalization is a producer concern rather than part of the text grammar.

## Image resources

An `img` with a `src` and no `srcset` serializes its single resource as a direct destination:

```text
@e2 img "Logo" -> /assets/logo.svg [alt=Logo]
```

The presence of `srcset` always selects the resource-set form, including when it has only one candidate. The optional fallback `src` is emitted first as `src`; each remaining key is its source-set descriptor, such as `480w` or `2x`:

```text
@e2 img "Scarpa rossa" -> {
  src -> /images/scarpa.jpg
  480w -> /images/scarpa-480.jpg
  960w -> /images/scarpa-960.jpg
} [alt="Scarpa rossa"]
```

Resource lines are not DOM children and do not receive `@eN` references. A consumer can identify the construct unambiguously because the opening `{` directly follows `->`; an action set never does.

## Properties

Properties are an ordered, comma-separated list inside square brackets:

```text
@e5 input "Email" [type=email, name=email, required, empty]
```

A property is either:

- a boolean property with no value, such as `required`, `disabled`, or `empty`; or
- a key/value property, such as `type=email` or `role=button`.

Attribute values remain unquoted only when every character is an ASCII letter, digit, underscore, or hyphen. All other values use quoted-string escaping.

```text
@e2 button "Buy" [type=submit, class="primary action", data-id=checkout_1]
```

Property names are emitted as normalized lowercase source or canonical names. A producer MUST NOT emit the same property name twice on one element.

VPM/1 defines the container grammar but does not impose one closed vocabulary of properties. Consumers SHOULD preserve unknown properties even if they do not assign behavior to them.

## Actions

Actions are an ordered, comma-separated list inside curly braces:

```text
@e3 button "Save" [type=submit] {?click, ?focus}
```

An action marker describes availability:

| Form | Meaning |
|---|---|
| `click` | Available |
| `?click` | Inferred but not runtime-verified |
| `!click` | Blocked by known or declared state |
| `-click` | Recognized but unsupported |

The current static HTML producer normally emits `?` for potential actions and `!` when declared disabled state blocks them. It cannot verify actual browser actionability.

The action vocabulary currently includes:

- `click`;
- `open`;
- `focus`;
- `fill`;
- `clear`;
- `check`;
- `uncheck`;
- `select`;
- `upload`.

Consumers MUST interpret an unverified action as a possibility, not as permission or proof that execution will succeed.

## Complete examples

### Form controls

```text
VPM/1
title Checkout
url https://shop.example/checkout

@e1 page [lang=en]
  @e2 main
    @e3 h1 "Checkout"
    @e4 form "Payment"
      @e5 input "Cardholder" [type=text, required, empty] {?fill, ?clear, ?focus}
      @e6 select "Country" [required] {?select, ?focus}
        @e7 option "Italy" [value=it]
        @e8 option "France" [value=fr]
      @e9 button "Pay" [type=submit] {?click, ?focus}
```

### Structured text

Text-bearing elements remain leaves when all meaningful content can be expressed as one name. When a meaningful descendant must remain addressable, the parent retains children instead:

```text
@e1 page
  @e2 ul
    @e3 li
      @e4 text "Read"
      @e5 a "more" -> /more {?click, ?open}
```

### Blocked action

```text
@e1 page
  @e2 button "Submit" [type=submit, disabled] {!click, !focus}
```

## Determinism guarantees

For the same source HTML, base URL, configuration, parser version, and PHP DOM interpretation, serialization is deterministic:

- filtering happens before references are finalized;
- traversal follows retained source order;
- names and whitespace are normalized consistently;
- properties and actions use fixed ordering;
- references are contiguous preorder values;
- output uses fixed indentation and line endings;
- the document ends with exactly one line feed.

Determinism does not mean that references survive input changes. Adding or removing an earlier retained node can renumber every following element.

## Format versus producer behavior

This specification answers: **How is a VPM/1 document represented?**

How a producer decides which VPM nodes, names, properties, actions, and selectors to create from HTML is outside the scope of VPM/1.
