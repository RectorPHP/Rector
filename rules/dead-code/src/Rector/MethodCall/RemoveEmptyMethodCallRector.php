<?php

declare(strict_types=1);

namespace Rector\DeadCode\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Parser;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\NodeTypeResolver\Node\AttributeKey;

/**
 * @see \Rector\DeadCode\Tests\Rector\MethodCall\RemoveEmptyMethodCallRector\RemoveEmptyMethodCallRectorTest
 */
final class RemoveEmptyMethodCallRector extends AbstractRector
{
    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Remove empty method call', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function callThis()
    {
    }
}

$some = new SomeClass();
$some->callThis();
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function callThis()
    {
    }
}

$some = new SomeClass();
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        /** @var Scope $scope */
        $scope = $node->var->getAttribute(AttributeKey::SCOPE);
        /** @var ObjectType $type */
        $type = $scope->getType($node->var);
        /** @var ClassReflection|null $classReflection */
        $classReflection = $type->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        /** @var Class_|null $class */
        $class = $this->getClass($classReflection, $type->getClassName());

        if ($class === null) {
            return null;
        }

        if ($this->isNonEmptyMethod($class, $node)) {
            return null;
        }

        $this->removeNode($node);
        return $node;
    }

    private function getClass(ClassReflection $classReflection, string $className): ?Class_
    {
        /** @var string $fileName */
        $fileName = $classReflection->getFileName();

        /** @var Node[] $contentNodes */
        $contentNodes = $this->parser->parse($this->smartFileSystem->readFile($fileName));
        $classes = $this->betterNodeFinder->findInstanceOf($contentNodes, Class_::class);

        if ($classes === []) {
            return null;
        }

        $reflectionClassName = $classReflection->getName();
        foreach ($classes as $class) {
            $shortClassName = $class->name;
            if ($reflectionClassName === $className) {
                break;
            }
        }

        return $class;
    }

    private function isNonEmptyMethod(Class_ $class, MethodCall $methodCall): bool
    {
        /** @var Identifier $methodIdentifier */
        $methodIdentifier = $methodCall->name;
        /** @var ClassMethod $classMethod */
        $classMethod = $class->getMethod((string) $methodIdentifier);

        return (bool) $this->betterNodeFinder->find($classMethod->stmts, function ($node): bool {
            return ! $node instanceof Nop;
        });
    }
}
