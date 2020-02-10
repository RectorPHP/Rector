<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocNodeFactory\Doctrine\Class_;

use Doctrine\ORM\Mapping\UniqueConstraint;
use Nette\Utils\Strings;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Class_\UniqueConstraintTagValueNode;

final class UniqueConstraintPhpDocNodeFactory
{
    /**
     * @var string
     */
    private const UNIQUE_CONSTRAINT_PATTERN = '#(?<tag>@(ORM\\\\)?UniqueConstraint)\((?<content>.*?)\),?#si';

    /**
     * @return UniqueConstraintTagValueNode[]
     */
    public function createUniqueConstraintTagValueNodes(?array $uniqueConstraints, string $annotationContent): array
    {
        if ($uniqueConstraints === null) {
            return [];
        }

        $uniqueConstraintContents = Strings::matchAll($annotationContent, self::UNIQUE_CONSTRAINT_PATTERN);

        $uniqueConstraintTagValueNodes = [];
        foreach ($uniqueConstraints as $key => $uniqueConstraint) {
            $subAnnotationContent = $uniqueConstraintContents[$key];

            $uniqueConstraintTagValueNodes[] = $this->createIndexOrUniqueConstantTagValueNode(
                $uniqueConstraint,
                $subAnnotationContent['content'],
                $subAnnotationContent['tag']
            );
        }

        return $uniqueConstraintTagValueNodes;
    }

    private function createIndexOrUniqueConstantTagValueNode(
        UniqueConstraint $uniqueConstraint,
        string $annotationContent,
        string $tag
    ): UniqueConstraintTagValueNode {
        // doctrine/orm compatibility between different versions
        $flags = property_exists($uniqueConstraint, 'flags') ? $uniqueConstraint->flags : [];

        return new UniqueConstraintTagValueNode(
            $uniqueConstraint->name,
            $uniqueConstraint->columns,
            $flags,
            $uniqueConstraint->options,
            $annotationContent,
            $tag
        );
    }
}
