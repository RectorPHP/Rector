<?php declare(strict_types=1);

namespace Rector\Rector\Contrib\Symfony\DependencyInjection;

use PhpParser\Node;
use Rector\Node\NodeFactory;
use Rector\NodeAnalyzer\MethodCallAnalyzer;
use Rector\Rector\AbstractRector;

final class ContainerBuilderCompileEnvArgumentRector extends AbstractRector
{
    /**
     * @var MethodCallAnalyzer
     */
    private $methodCallAnalyzer;

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    public function __construct(MethodCallAnalyzer $methodCallAnalyzer, NodeFactory $nodeFactory)
    {
        $this->methodCallAnalyzer = $methodCallAnalyzer;
        $this->nodeFactory = $nodeFactory;
    }

    public function isCandidate(Node $node): bool
    {
        $isMethodCall = $this->methodCallAnalyzer->isTypeAndMethods(
            $node,
            'Symfony\Component\DependencyInjection\ContainerBuilder',
            ['compile']
        );

        if ($isMethodCall === false) {
            return false;
        }

        $arguments = $node->args;

        // already has an argument
        return count($arguments) !== 1;
    }

    /**
     * @param Node\Expr\MethodCall $methodCallNode
     */
    public function refactor(Node $methodCallNode): ?Node
    {
        $methodCallNode->args = $this->nodeFactory->createArgs([true]);

        return $methodCallNode;
    }
}
