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
    'patchers' => [
        static function (string $filePath, string $prefix, string $content): string {
            if (str_contains($filePath, 'src/Supportive/Console/Application.php')) {
                // resolve current tag
                exec('git tag --points-at', $output, $result_code);

                if (0 !== $result_code) {
                    throw new InvalidArgumentException('Ensure to run compile from composer git repository clone and that git binary is available.');
                }

                if ([] !== $output) {
                    $tag = $output[0];

                    if ('' !== $tag) {
                        return str_replace('@git-version@', $tag, $content);
                    }
                }
            }

            return $content;
        },
    ],
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
