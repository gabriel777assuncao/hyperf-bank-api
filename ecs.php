<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/test',
    ])
    ->withRootFiles()
    ->withRules([
        NoUnusedImportsFixer::class,
    ])
    ->withPreparedSets(
        spaces: true,
        namespaces: true,
        docblocks: true,
        arrays: true,
        comments: true,
    );
