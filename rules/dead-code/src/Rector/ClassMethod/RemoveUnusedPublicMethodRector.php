<?php

declare(strict_types=1);

namespace Rector\DeadCode\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Caching\Contract\Rector\ZeroCacheRectorInterface;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeCollector\ValueObject\ArrayCallable;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Rector\NodeTypeResolver\Node\AttributeKey;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;

/**
 * @see \Rector\DeadCode\Tests\Rector\ClassMethod\RemoveUnusedPublicMethodRector\RemoveUnusedPublicMethodRectorTest
 */
final class RemoveUnusedPublicMethodRector extends AbstractRector implements ZeroCacheRectorInterface
{
    /**
     * @var MethodCall[]|StaticCall[]|ArrayCallable[]
     */
    private $calls = [];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove unused public method',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class SomeClass
{
    public function unusedpublicMethod()
    {
        // ...
    }

    public function execute()
    {
        // ...
    }

    public function run()
    {
        $obj = new self;
        $obj->execute();
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
class SomeClass
{
    public function execute()
    {
        // ...
    }

    public function run()
    {
        $obj = new self;
        $obj->execute();
    }
}
CODE_SAMPLE
                ),

            ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $node->isPublic()) {
            return null;
        }

        $this->calls = array_merge($this->calls, $this->nodeRepository->findCallsByClassMethod($node));
        if ($this->calls === []) {
            $this->removeNode($node);
            return $node;
        }

        $isFoundCall = (bool) $this->betterNodeFinder->findFirst($node->stmts, function (Node $node): bool {
            if (! $node instanceof MethodCall) {
                return false;
            }

            $className = $this->getMethodCallClassName($node);
            if ($className === null) {
                return false;
            }

            return false;
        });

        if ($isFoundCall) {
            return null;
        }

        $this->removeNode($node);
        return $node;
    }

    private function getMethodCallClassName(MethodCall $methodCall): ?string
    {
        $scope = $methodCall->getAttribute(AttributeKey::SCOPE);
        if ($scope === null) {
            return null;
        }

        $type = $scope->getType($n->var);
        if ($type instanceof ObjectType) {
            return $type->getClassName();
        }

        if ($type instanceof ThisType) {
            $type = $type->getStaticObjectType();
            return $type->getClassName();
        }

        return null;
    }
}
