<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocNodeFactory\Doctrine\Class_;

use Doctrine\ORM\Mapping\Table;
use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Class_\TableTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNodeFactory\AbstractPhpDocNodeFactory;
use Rector\Core\Exception\ShouldNotHappenException;

final class TablePhpDocNodeFactory extends AbstractPhpDocNodeFactory
{
    /**
     * @var IndexPhpDocNodeFactory
     */
    private $indexPhpDocNodeFactory;

    /**
     * @var UniqueConstraintPhpDocNodeFactory
     */
    private $uniqueConstraintPhpDocNodeFactory;

    public function __construct(
        IndexPhpDocNodeFactory $indexPhpDocNodeFactory,
        UniqueConstraintPhpDocNodeFactory $uniqueConstraintPhpDocNodeFactory
    ) {
        $this->indexPhpDocNodeFactory = $indexPhpDocNodeFactory;
        $this->uniqueConstraintPhpDocNodeFactory = $uniqueConstraintPhpDocNodeFactory;
    }

    public function getClass(): string
    {
        return Table::class;
    }

    public function createFromNodeAndTokens(Node $node, TokenIterator $tokenIterator): ?PhpDocTagValueNode
    {
        if (! $node instanceof Class_) {
            throw new ShouldNotHappenException();
        }

        /** @var Table|null $table */
        $table = $this->nodeAnnotationReader->readClassAnnotation($node, $this->getClass());
        if ($table === null) {
            return null;
        }

        $annotationContent = $this->resolveContentFromTokenIterator($tokenIterator);

        $indexesContent = $this->annotationContentResolver->resolveNestedKey($annotationContent, 'indexes');
        $indexTagValueNodes = $this->indexPhpDocNodeFactory->createIndexTagValueNodes(
            $table->indexes,
            $indexesContent
        );

        [$indexesOpeningSpace, $indexesClosingSpace] = $this->matchCurlyBracketOpeningAndClosingSpace(
            $indexesContent
        );

        $haveIndexesFinalComma = (bool) Strings::match($indexesContent, '#,(\s+)?}$#m');
        $uniqueConstraintsContent = $this->annotationContentResolver->resolveNestedKey(
            $annotationContent,
            'uniqueConstraints'
        );

        [$uniqueConstraintsOpeningSpace, $uniqueConstraintsClosingSpace] = $this->matchCurlyBracketOpeningAndClosingSpace(
            $uniqueConstraintsContent
        );

        $uniqueConstraintTagValueNodes = $this->uniqueConstraintPhpDocNodeFactory->createUniqueConstraintTagValueNodes(
            $table->uniqueConstraints,
            $uniqueConstraintsContent
        );

        $haveUniqueConstraintsFinalComma = (bool) Strings::match($uniqueConstraintsContent, '#,(\s+)?}$#m');

        return new TableTagValueNode(
            $table->name,
            $table->schema,
            $indexTagValueNodes,
            $uniqueConstraintTagValueNodes,
            $table->options,
            $annotationContent,
            $haveIndexesFinalComma,
            $haveUniqueConstraintsFinalComma,
            $indexesOpeningSpace,
            $indexesClosingSpace,
            $uniqueConstraintsOpeningSpace,
            $uniqueConstraintsClosingSpace
        );
    }
}
