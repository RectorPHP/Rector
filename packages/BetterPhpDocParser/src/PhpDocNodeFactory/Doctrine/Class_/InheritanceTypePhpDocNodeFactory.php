<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocNodeFactory\Doctrine\Class_;

use Doctrine\ORM\Mapping\InheritanceType;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Class_\InheritanceTypeTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNodeFactory\AbstractPhpDocNodeFactory;
use Rector\Exception\ShouldNotHappenException;

final class InheritanceTypePhpDocNodeFactory extends AbstractPhpDocNodeFactory
{
    public function getClass(): string
    {
        return InheritanceType::class;
    }

    public function createFromNodeAndTokens(Node $node, TokenIterator $tokenIterator): ?PhpDocTagValueNode
    {
        if (! $node instanceof Class_) {
            throw new ShouldNotHappenException();
        }

        /** @var InheritanceType|null $inheritanceType */
        $inheritanceType = $this->nodeAnnotationReader->readClassAnnotation($node, $this->getClass());
        if ($inheritanceType === null) {
            return null;
        }

        $annotationContent = $this->resolveContentFromTokenIterator($tokenIterator);

        return new InheritanceTypeTagValueNode($inheritanceType->value, $annotationContent);
    }
}
