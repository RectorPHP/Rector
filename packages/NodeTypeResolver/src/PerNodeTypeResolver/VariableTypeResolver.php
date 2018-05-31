<?php declare(strict_types=1);

namespace Rector\NodeTypeResolver\PerNodeTypeResolver;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use Rector\Node\Attribute;
use Rector\NodeTypeResolver\Contract\NodeTypeResolverAwareInterface;
use Rector\NodeTypeResolver\Contract\PerNodeTypeResolver\PerNodeTypeResolverInterface;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Rector\NodeTypeResolver\TypeContext;

final class VariableTypeResolver implements PerNodeTypeResolverInterface, NodeTypeResolverAwareInterface
{
    /**
     * @var TypeContext
     */
    private $typeContext;

    /**
     * @var NodeTypeResolver
     */
    private $nodeTypeResolver;

    public function __construct(TypeContext $typeContext)
    {
        $this->typeContext = $typeContext;
    }

    /**
     * @return string[]
     */
    public function getNodeClasses(): array
    {
        return [Variable::class];
    }

    /**
     * @param Variable $variableNode
     * @return string[]
     */
    public function resolve(Node $variableNode): array
    {
        if ($variableNode->name === 'this') {
            $classNode = $variableNode->getAttribute(Attribute::CLASS_NODE);
            if ($classNode === null) {
                // don't know yet
                return [];
            }

            return $this->nodeTypeResolver->resolve($classNode);
        }

        if ($variableNode->name instanceof Variable) {
            return $this->nodeTypeResolver->resolve($variableNode->name);
        }

        $variableTypes = $this->typeContext->getTypesForVariable((string) $variableNode->name);
        if ($variableTypes) {
            return $variableTypes;
        }

        if ($variableNode->getAttribute(Attribute::PARENT_NODE) instanceof Assign) {
            return $this->nodeTypeResolver->resolve($variableNode->getAttribute(Attribute::PARENT_NODE));
        }

        return [];
    }

    public function setNodeTypeResolver(NodeTypeResolver $nodeTypeResolver): void
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
    }
}
