<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoWhitespaceBeforeCommaInArrayFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedInterfacesFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedTraitsFixer;
use PhpCsFixer\Fixer\ControlStructure\NoSuperfluousElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\GroupImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\SingleSpaceAroundConstructFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderStaticFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/test',
    ]);

    $ecsConfig->rulesWithConfiguration([
        ArraySyntaxFixer::class => ['syntax' => 'short'],
        ConcatSpaceFixer::class => ['spacing' => 'none'],
        TrailingCommaInMultilineFixer::class => ['elements' => ['arrays']],
        PhpUnitMethodCasingFixer::class => ['case' => 'snake_case'],
        PhpUnitDataProviderStaticFixer::class => ['force' => true],
    ]);

    $ecsConfig->rules([
        ArrayIndentationFixer::class,
        BlankLineBeforeStatementFixer::class,
        CastSpacesFixer::class,
        GlobalNamespaceImportFixer::class,
        GroupImportFixer::class,
        MethodChainingIndentationFixer::class,
        NoSuperfluousElseifFixer::class,
        NotOperatorWithSuccessorSpaceFixer::class,
        NoUnusedImportsFixer::class,
        NoWhitespaceBeforeCommaInArrayFixer::class,
        OrderedInterfacesFixer::class,
        OrderedTraitsFixer::class,
        PhpUnitDataProviderStaticFixer::class,
        SingleQuoteFixer::class,
        SingleSpaceAroundConstructFixer::class,
    ]);
};
