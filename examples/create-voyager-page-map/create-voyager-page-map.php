<?php

declare(strict_types=1);

use NorthFoundry\VoyagerPageMap\Configuration\VoyagerPageMapConfiguration;
use NorthFoundry\VoyagerPageMap\VoyagerPageMap;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$exampleDirectory = __DIR__;
$pageName = 'turin-weekend-guide';
$sourcePath = $exampleDirectory . '/' . $pageName . '.html';
$vpmOutputPath = $exampleDirectory . '/' . $pageName . '.vpm';

$html = file_get_contents($sourcePath);
if ($html === false) {
    throw new RuntimeException(sprintf('Unable to read the source page: %s', $sourcePath));
}

$configuration = VoyagerPageMapConfiguration::agent()->withRelativeUrlResolution();
$pageMap = VoyagerPageMap::fromHtml(
    $html,
    baseUrl: 'https://example.test/guides/' . $pageName . '.html',
    configuration: $configuration,
);

if (file_put_contents($vpmOutputPath, $pageMap->toText()) === false) {
    throw new RuntimeException(sprintf('Unable to write the VPM output: %s', $vpmOutputPath));
}

printf("Created %s\n", basename($vpmOutputPath));
