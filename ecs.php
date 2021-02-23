<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Operator\UnaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveCommentedCodeFixer;
use Symplify\CodingStandard\Fixer\LineLength\DocBlockLineLengthFixer;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(GeneralPhpdocAnnotationRemoveFixer::class)
        ->call('configure', [[
            'annotations' => ['throws', 'author', 'package', 'group'],
        ]]);

    $services->set(NoSuperfluousPhpdocTagsFixer::class)
        ->call('configure', [[
            'allow_mixed' => true,
        ]]);

    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PATHS, [
        __DIR__ . '/bin',
        __DIR__ . '/src',
        __DIR__ . '/packages',
        __DIR__ . '/rules',
        __DIR__ . '/tests',
        __DIR__ . '/utils',
        __DIR__ . '/config',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
        __DIR__ . '/config/set',
    ]);

    $parameters->set(Option::SETS, [SetList::PSR_12, SetList::SYMPLIFY, SetList::COMMON, SetList::CLEAN_CODE]);

    $parameters->set(Option::SKIP, [
        '*/Source/*',
        '*/Fixture/*',
        '*/Expected/*',

        # generated from /vendor
        __DIR__ . '/packages/DoctrineAnnotationGenerated/ConstantPreservingDocParser.php',
        __DIR__ . '/packages/DoctrineAnnotationGenerated/ConstantPreservingAnnotationReader.php',

        UnaryOperatorSpacesFixer::class,

        // buggy with specific markdown snippet file in docs/rules_overview.md
        ArrayListItemNewlineFixer::class,
        BlankLineAfterOpeningTagFixer::class,

        // buggy - @todo fix on Symplify master
        RemoveCommentedCodeFixer::class,
        DocBlockLineLengthFixer::class,

        // breaks on-purpose annotated variables
        ReturnAssignmentFixer::class,

        PhpdocTypesFixer::class => [__DIR__ . '/rules/Php74/Rector/Double/RealToFloatTypeCastRector.php'],

        // buggy on "Float" class
        PhpUnitStrictFixer::class => [
            __DIR__ . '/packages-tests/BetterPhpDocParser/PhpDocInfo/PhpDocInfo/PhpDocInfoTest.php',
            __DIR__ . '/tests/PhpParser/Node/NodeFactoryTest.php',
            '*TypeResolverTest.php',
        ],
    ]);

    $parameters->set(Option::LINE_ENDING, "\n");
};
