<?php

declare(strict_types=1);

use EmanueleCoppola\PHPeg\Loader\CleanPeg\CleanPegGrammarLoader;

require __DIR__ . '/vendor/autoload.php';

$grammarPath = __DIR__ . '/voyager-page-map.peg';
$defaultVpmPath = __DIR__ . '/turin-weekend-guide.vpm';
$vpmPath = $argv[1] ?? $defaultVpmPath;

$input = file_get_contents($vpmPath);
if ($input === false) {
    throw new RuntimeException(sprintf('Unable to read VPM input: %s', $vpmPath));
}

// VPM indentation and line endings are significant, so CleanPeg must not skip them.
$loader = new CleanPegGrammarLoader(skipPattern: null);
$grammar = $loader->fromFile($grammarPath, startRule: 'VoyagerPageMap');
$result = $grammar->parse($input);

if (!$result->isSuccess()) {
    $message = $result->error()?->message() ?? 'Unknown parsing error.';
    fwrite(STDERR, sprintf("VPM parsing failed for %s: %s\n", $vpmPath, $message));
    exit(1);
}

$elementCount = preg_match_all('/^\s*@e\d+\b/m', $input);
if ($elementCount === false) {
    throw new RuntimeException('Unable to count VPM element references.');
}

printf(
    "Parsed %s successfully with voyager-page-map.peg (%d VPM elements).\n",
    basename($vpmPath),
    $elementCount,
);
