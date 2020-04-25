<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\Tests\PhpDocParser\TagValueNodeReprint;

use Iterator;
use Nette\Utils\Strings;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Class_\EntityTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Class_\TableTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Property_\ColumnTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Property_\CustomIdGeneratorTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Property_\GeneratedValueTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Property_\JoinTableTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Gedmo\BlameableTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Gedmo\SlugTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Symfony\SymfonyRouteTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Symfony\Validator\Constraints\AssertChoiceTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Symfony\Validator\Constraints\AssertTypeTagValueNode;
use Rector\BetterPhpDocParser\Tests\PhpDocParser\AbstractPhpDocInfoTest;

final class TagValueNodeReprintTest extends AbstractPhpDocInfoTest
{
    /**
     * @dataProvider provideData()
     * @param class-string $tagValueNodeClass
     */
    public function test(string $filePath, string $tagValueNodeClass): void
    {
        if (Strings::endsWith($filePath, 'QuotesInNestedArray.php')) {
            $this->markTestSkipped('Quoting nested keys in annotations is in progress');
        }

        $this->doTestPrintedPhpDocInfo($filePath, $tagValueNodeClass);
    }

    public function provideData(): Iterator
    {
        foreach ($this->getDirectoriesByTagValueNodes() as $tagValueNode => $directory) {
            foreach ($this->findFilesFromDirectory($directory) as $filePath) {
                yield [$filePath, $tagValueNode];
            }
        }
    }

    /**
     * @return string[]
     */
    private function getDirectoriesByTagValueNodes(): array
    {
        return [
            BlameableTagValueNode::class => __DIR__ . '/Fixture/Blameable',
            SlugTagValueNode::class => __DIR__ . '/Fixture/Slug',
            AssertChoiceTagValueNode::class => __DIR__ . '/Fixture/AssertChoice',
            AssertTypeTagValueNode::class => __DIR__ . '/Fixture/AssertType',
            SymfonyRouteTagValueNode::class => __DIR__ . '/Fixture/SymfonyRoute',
            // Doctrine
            ColumnTagValueNode::class => __DIR__ . '/Fixture/DoctrineColumn',
            JoinTableTagValueNode::class => __DIR__ . '/Fixture/DoctrineJoinTable',
            EntityTagValueNode::class => __DIR__ . '/Fixture/DoctrineEntity',
            TableTagValueNode::class => __DIR__ . '/Fixture/DoctrineTable',
            CustomIdGeneratorTagValueNode::class => __DIR__ . '/Fixture/DoctrineCustomIdGenerator',
            GeneratedValueTagValueNode::class => __DIR__ . '/Fixture/DoctrineGeneratedValue',
        ];
    }
}
