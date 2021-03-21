<?php

declare(strict_types=1);

namespace Rector\Tests\BetterPhpDocParser\PhpDocParser\TagValueNodeReprint;

use Iterator;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\Class_\EmbeddedTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\Class_\EntityTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\Class_\TableTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\Property_\ColumnTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\Property_\CustomIdGeneratorTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\Property_\GeneratedValueTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Doctrine\Property_\JoinTableTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Gedmo\BlameableTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Gedmo\SlugTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Sensio\SensioMethodTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocNode\Sensio\SensioTemplateTagValueNode;
use Rector\Symfony\PhpDoc\Node\AssertChoiceTagValueNode;
use Rector\Symfony\PhpDoc\Node\AssertTypeTagValueNode;
use Rector\Symfony\PhpDoc\Node\SymfonyRouteTagValueNode;
use Rector\Tests\BetterPhpDocParser\PhpDocParser\AbstractPhpDocInfoTest;
use Symplify\SmartFileSystem\SmartFileInfo;

final class TagValueNodeReprintTest extends AbstractPhpDocInfoTest
{
    /**
     * @dataProvider provideData()
     * @param class-string<Node> $tagValueNodeClass
     */
    public function test(SmartFileInfo $fileInfo, string $tagValueNodeClass): void
    {
        $this->doTestPrintedPhpDocInfo($fileInfo, $tagValueNodeClass);
    }

    /**
     * @return Iterator<mixed[]>
     */
    public function provideData(): Iterator
    {
        foreach ($this->getDirectoriesByTagValueNodes() as $tagValueNode => $directory) {
            $filesInDirectory = $this->findFilesFromDirectory($directory);
            foreach ($filesInDirectory as $fileInDirectory) {
                foreach ($fileInDirectory as $singleFileInDirectory) {
                    yield [$singleFileInDirectory, $tagValueNode];
                }
            }
        }
    }

    /**
     * @return array<class-string, string>
     */
    private function getDirectoriesByTagValueNodes(): array
    {
        return [
            BlameableTagValueNode::class => __DIR__ . '/Fixture/Blameable',
            SlugTagValueNode::class => __DIR__ . '/Fixture/Gedmo',
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
            EmbeddedTagValueNode::class => __DIR__ . '/Fixture/DoctrineEmbedded',
            // special case
            GenericTagValueNode::class => __DIR__ . '/Fixture/ConstantReference',
            SensioTemplateTagValueNode::class => __DIR__ . '/Fixture/SensioTemplate',
            SensioMethodTagValueNode::class => __DIR__ . '/Fixture/SensioMethod',
            TemplateTagValueNode::class => __DIR__ . '/Fixture/Native/Template',
            VarTagValueNode::class => __DIR__ . '/Fixture/Native/VarTag',
        ];
    }
}
