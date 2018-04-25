<?php declare(strict_types=1);

namespace Rector\NodeTypeResolver\Tests\PerNodeTypeResolver\ClassLikeTypeResolver;

use Iterator;
use PhpParser\Node\Expr\Variable;
use Rector\NodeTypeResolver\Tests\PerNodeTypeResolver\AbstractNodeTypeResolverTest;

/**
 * @covers \Rector\NodeTypeResolver\PerNodeTypeResolver\ClassLikeTypeResolver
 */
final class ClassLikeTypeResolverTest extends AbstractNodeTypeResolverTest
{
    /**
     * @dataProvider provideTypeForNodesAndFilesData()
     * @param string[] $expectedTypes
     */
    public function test(string $file, int $nodePosition, array $expectedTypes): void
    {
        $variableNodes = $this->getNodesForFileOfType($file, Variable::class);

        $this->assertSame($expectedTypes, $this->nodeTypeResolver->resolve($variableNodes[$nodePosition]));
    }

    public function provideTypeForNodesAndFilesData(): Iterator
    {
        # assign of "new <name>"
        yield [__DIR__ . '/Source/ClassWithParent.php.inc', 0, [
            'SomeNamespace\SomeClass',
            'SomeNamespace\SomeInterface',
        ]];
    }
}
