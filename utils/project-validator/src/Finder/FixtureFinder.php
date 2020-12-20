<?php

declare(strict_types=1);

namespace Rector\Utils\ProjectValidator\Finder;

use Symfony\Component\Finder\Finder;
use Symplify\SmartFileSystem\Finder\FinderSanitizer;
use Symplify\SmartFileSystem\SmartFileInfo;

final class FixtureFinder
{
    /**
     * @var FinderSanitizer
     */
    private $finderSanitizer;

    public function __construct(FinderSanitizer $finderSanitizer)
    {
        $this->finderSanitizer = $finderSanitizer;
    }

    /**
     * @return SmartFileInfo[]
     */
    public function findFixtureFileInfos(): array
    {
        $finder = new Finder();
        $finder = $finder->files()
            ->name('#\.php\.inc$#')
            ->notName('#empty_file\.php\.inc$#')
            ->path('#/Fixture/#')
            ->notPath('#/blade-template/#')
            ->notPath('#/RenameNamespaceRector/#')
            ->notPath('#/TemplateAnnotationToThisRenderRector/#')
            ->notPath('#bootstrap_names\.php\.inc#')
            ->notPath('#trait_name\.php\.inc#')
            ->notName('#_\.php\.inc$#')
            ->notPath('#/ParamTypeDeclarationRector/#')
            ->notPath('#/ReturnTypeDeclarationRector/#')
            ->in(__DIR__ . '/../../../../tests')
            ->in(__DIR__ . '/../../../../packages');

        return $this->finderSanitizer->sanitize($finder);
    }
}
