<?php declare(strict_types=1);

namespace Rector\NodeTypeResolver\PerNodeTypeResolver;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use Rector\BetterReflection\Reflector\MethodReflector;
use Rector\Node\Attribute;
use Rector\NodeTypeResolver\Contract\NodeTypeResolverAwareInterface;
use Rector\NodeTypeResolver\Contract\PerNodeTypeResolver\PerNodeTypeResolverInterface;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Rector\NodeTypeResolver\TypeContext;

/**
 * This resolves return type of method call,
 * not types of its elements.
 */
final class MethodCallTypeResolver implements PerNodeTypeResolverInterface, NodeTypeResolverAwareInterface
{
    /**
     * @var string[]
     */
    private $allowedVarNodeClasses = [
        // chain method calls: $this->someCall()->anotherCall()
        MethodCall::class,
        // $this->someCall()
        Variable::class,
        // $this->property->someCall()
        PropertyFetch::class,
    ];

    /**
     * @var TypeContext
     */
    private $typeContext;

    /**
     * @var MethodReflector
     */
    private $methodReflector;

    /**
     * @var NodeTypeResolver
     */
    private $nodeTypeResolver;

    public function __construct(TypeContext $typeContext, MethodReflector $methodReflector)
    {
        $this->typeContext = $typeContext;
        $this->methodReflector = $methodReflector;
    }

    /**
     * @return string[]
     */
    public function getNodeClasses(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $methodCallNode
     * @return string[]
     */
    public function resolve(Node $methodCallNode): array
    {
        $parentCallerTypes = $this->resolveMethodCallVarTypes($methodCallNode);

        if (! $methodCallNode->name instanceof Identifier) {
            return [];
        }

        $methodName = $methodCallNode->name->toString();

        if (count($parentCallerTypes) === 0 || ! $methodName) {
            return [];
        }

        return $this->resolveMethodReflectionReturnTypes($methodCallNode, $parentCallerTypes, $methodName);
    }

    public function setNodeTypeResolver(NodeTypeResolver $nodeTypeResolver): void
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
    }

    /**
     * Resolve for:
     * - getMethod(): ReturnType
     *
     * @param string[] $methodCallerTypes
     * @return string[]
     */
    private function resolveMethodReflectionReturnTypes(
        MethodCall $methodCallNode,
        array $methodCallerTypes,
        string $method
    ): array {
        $methodReturnTypes = $this->methodReflector->resolveReturnTypesForTypesAndMethod($methodCallerTypes, $method);
        if (count($methodReturnTypes) > 0) {
            return array_unique($methodReturnTypes);
        }

        $variableName = $this->getVariableToAssignTo($methodCallNode);
        if ($variableName === null) {
            return [];
        }

        $this->typeContext->addVariableWithTypes($variableName, $methodReturnTypes);

        return $methodReturnTypes;
    }

    private function getVariableToAssignTo(MethodCall $methodCallNode): ?string
    {
        $assignNode = $methodCallNode->getAttribute(Attribute::PARENT_NODE);
        if (! $assignNode instanceof Assign) {
            return null;
        }

        if ($assignNode->var instanceof Variable) {
            return (string) $assignNode->var->name;
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function resolveMethodCallVarTypes(MethodCall $methodCallNode): array
    {
        $varNodeClass = get_class($methodCallNode->var);

        if (in_array($varNodeClass, $this->allowedVarNodeClasses, true)) {
            return $this->nodeTypeResolver->resolve($methodCallNode->var);
        }

        return [];
    }
}
