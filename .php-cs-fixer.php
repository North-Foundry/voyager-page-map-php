<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS3x0' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
    ])
    ->setFinder(PhpCsFixer\Finder::create()->in(__DIR__ . '/src')->in(__DIR__ . '/tests'));
