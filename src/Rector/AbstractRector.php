<?php declare(strict_types=1);

namespace Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Rector\Builder\ExpressionAdder;
use Rector\Builder\PropertyAdder;
use Rector\Contract\Rector\PhpRectorInterface;

abstract class AbstractRector extends NodeVisitorAbstract implements PhpRectorInterface
{
    /**
     * @var bool
     */
    protected $removeNode = false;

    /**
     * @var ExpressionAdder
     */
    private $expressionAdder;

    /**
     * @var PropertyAdder
     */
    private $propertyAdder;

    /**
     * @required
     */
    public function setAbstractRectorDependencies(PropertyAdder $propertyAdder, ExpressionAdder $expressionAdder): void
    {
        $this->propertyAdder = $propertyAdder;
        $this->expressionAdder = $expressionAdder;
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    final public function beforeTraverse(array $nodes): array
    {
        return $nodes;
    }

    /**
     * @return null|int|Node
     */
    final public function enterNode(Node $node)
    {
        if (method_exists($this, 'getNodeType')) {
            if (! is_a($node, $this->getNodeType(), true)) { // == basically "isCandidate()" condition
                return null;
            }
        } elseif (! $this->isCandidate($node)) {
            return null;
        }

        $newNode = $this->refactor($node);
        if ($newNode !== null) {
            return $newNode;
        }

        return NodeTraverser::DONT_TRAVERSE_CHILDREN;
    }

    /**
     * @return bool|int|Node
     */
    public function leaveNode(Node $node)
    {
        if ($this->removeNode) {
            $this->removeNode = false;
            return NodeTraverser::REMOVE_NODE;
        }

        return $node;
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function afterTraverse(array $nodes): array
    {
        $nodes = $this->expressionAdder->addExpressionsToNodes($nodes);
        return $this->propertyAdder->addPropertiesToNodes($nodes);
    }

    protected function addNodeAfterNode(Expr $newNode, Node $positionNode): void
    {
        $this->expressionAdder->addNodeAfterNode($newNode, $positionNode);
    }
}
