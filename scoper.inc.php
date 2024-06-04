<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$polyfillsBootstrap = Finder::create()
    ->files()
    ->in(__DIR__.'/vendor/symfony/polyfill-*')
    ->name('*.php');

return [
    'prefix' => 'DEPTRAC_INTERNAL',
    'finders' => [
        Finder::create()->files()->in([
            'config',
            'src',
            'vendor',
        ])->append([
            'bin/deptrac',
            'composer.json',
        ])->exclude([
            'bin',
            'tests',
            'test',
        ])->notName('/.*\\.(xml|md|dist|neon|zip)|Makefile|composer\\.json|composer\\.lock/'),
    ],
    'patchers' => [],
    'tag-declarations-as-internal' => false,
    'exclude-files' => array_map(
        static function ($file) {
            return $file->getPathName();
        },
        iterator_to_array($polyfillsBootstrap)
    ),
    'exclude-namespaces' => [
        'Qossmic\Deptrac',
        'Symfony\Polyfill',
    ],
    'expose-functions' => ['trigger_deprecation'],
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
];
