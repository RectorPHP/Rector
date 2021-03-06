<?php

declare (strict_types=1);
namespace Rector\TypeDeclaration\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\UnionType as PhpParserUnionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VoidType;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\Reflection\ReflectionResolver;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind;
use Rector\TypeDeclaration\NodeAnalyzer\TypeNodeUnwrapper;
use Rector\TypeDeclaration\TypeInferer\ReturnTypeInferer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Rector\Tests\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector\ReturnTypeFromStrictTypedCallRectorTest
 */
final class ReturnTypeFromStrictTypedCallRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @var \Rector\TypeDeclaration\NodeAnalyzer\TypeNodeUnwrapper
     */
    private $typeNodeUnwrapper;
    /**
     * @var \Rector\Core\Reflection\ReflectionResolver
     */
    private $reflectionResolver;
    /**
     * @var \Rector\TypeDeclaration\TypeInferer\ReturnTypeInferer
     */
    private $returnTypeInferer;
    public function __construct(\Rector\TypeDeclaration\NodeAnalyzer\TypeNodeUnwrapper $typeNodeUnwrapper, \Rector\Core\Reflection\ReflectionResolver $reflectionResolver, \Rector\TypeDeclaration\TypeInferer\ReturnTypeInferer $returnTypeInferer)
    {
        $this->typeNodeUnwrapper = $typeNodeUnwrapper;
        $this->reflectionResolver = $reflectionResolver;
        $this->returnTypeInferer = $returnTypeInferer;
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Add return type from strict return type of call', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function getData()
    {
        return $this->getNumber();
    }

    private function getNumber(): int
    {
        return 1000;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function getData(): int
    {
        return $this->getNumber();
    }

    private function getNumber(): int
    {
        return 1000;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Stmt\ClassMethod::class, \PhpParser\Node\Stmt\Function_::class, \PhpParser\Node\Expr\Closure::class];
    }
    /**
     * @param ClassMethod|Function_|Closure $node
     */
    public function refactor(\PhpParser\Node $node) : ?\PhpParser\Node
    {
        if ($this->isSkipped($node)) {
            return null;
        }
        if ($this->isUnionPossibleReturnsVoid($node)) {
            return null;
        }
        /** @var Return_[] $returns */
        $returns = $this->betterNodeFinder->find((array) $node->stmts, function (\PhpParser\Node $n) use($node) {
            $currentFunctionLike = $this->betterNodeFinder->findParentType($n, \PhpParser\Node\FunctionLike::class);
            if ($currentFunctionLike === $node) {
                return $n instanceof \PhpParser\Node\Stmt\Return_;
            }
            $currentReturn = $this->betterNodeFinder->findParentType($n, \PhpParser\Node\Stmt\Return_::class);
            if (!$currentReturn instanceof \PhpParser\Node\Stmt\Return_) {
                return \false;
            }
            $currentFunctionLike = $this->betterNodeFinder->findParentType($currentReturn, \PhpParser\Node\FunctionLike::class);
            if ($currentFunctionLike !== $node) {
                return \false;
            }
            return $n instanceof \PhpParser\Node\Stmt\Return_;
        });
        $returnedStrictTypes = $this->collectStrictReturnTypes($returns);
        if ($returnedStrictTypes === []) {
            return null;
        }
        if (\count($returnedStrictTypes) === 1) {
            return $this->refactorSingleReturnType($returns[0], $returnedStrictTypes[0], $node);
        }
        if ($this->isAtLeastPhpVersion(\Rector\Core\ValueObject\PhpVersionFeature::UNION_TYPES)) {
            /** @var PhpParserUnionType[] $returnedStrictTypes */
            $unwrappedTypes = $this->typeNodeUnwrapper->unwrapNullableUnionTypes($returnedStrictTypes);
            $returnType = new \PhpParser\Node\UnionType($unwrappedTypes);
            $node->returnType = $returnType;
            return $node;
        }
        return null;
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure $node
     */
    private function isUnionPossibleReturnsVoid($node) : bool
    {
        $inferReturnType = $this->returnTypeInferer->inferFunctionLike($node);
        if ($inferReturnType instanceof \PHPStan\Type\UnionType) {
            foreach ($inferReturnType->getTypes() as $type) {
                if ($type instanceof \PHPStan\Type\VoidType) {
                    return \true;
                }
            }
        }
        return \false;
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure $node
     * @return \PhpParser\Node\Expr\Closure|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_
     */
    private function processSingleUnionType($node, \PHPStan\Type\UnionType $unionType, \PhpParser\Node\NullableType $nullableType)
    {
        $types = $unionType->getTypes();
        $returnType = $types[0] instanceof \PHPStan\Type\ObjectType && $types[1] instanceof \PHPStan\Type\NullType ? new \PhpParser\Node\NullableType(new \PhpParser\Node\Name\FullyQualified($types[0]->getClassName())) : $nullableType;
        $node->returnType = $returnType;
        return $node;
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure $node
     */
    private function isSkipped($node) : bool
    {
        if (!$this->phpVersionProvider->isAtLeastPhpVersion(\Rector\Core\ValueObject\PhpVersionFeature::SCALAR_TYPES)) {
            return \true;
        }
        if ($node->returnType !== null) {
            return \true;
        }
        return $node instanceof \PhpParser\Node\Stmt\ClassMethod && $node->isMagic();
    }
    /**
     * @param Return_[] $returns
     * @return array<Identifier|Name|NullableType|PhpParserUnionType>
     */
    private function collectStrictReturnTypes(array $returns) : array
    {
        $returnedStrictTypeNodes = [];
        foreach ($returns as $return) {
            if ($return->expr === null) {
                return [];
            }
            $returnedExpr = $return->expr;
            if ($returnedExpr instanceof \PhpParser\Node\Expr\MethodCall || $returnedExpr instanceof \PhpParser\Node\Expr\StaticCall || $returnedExpr instanceof \PhpParser\Node\Expr\FuncCall) {
                $returnNode = $this->resolveMethodCallReturnNode($returnedExpr);
            } else {
                return [];
            }
            if (!$returnNode instanceof \PhpParser\Node) {
                return [];
            }
            $returnedStrictTypeNodes[] = $returnNode;
        }
        return $this->typeNodeUnwrapper->uniquateNodes($returnedStrictTypeNodes);
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\FuncCall $call
     */
    private function resolveMethodCallReturnNode($call) : ?\PhpParser\Node
    {
        $methodReflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($call);
        if ($methodReflection === null) {
            return null;
        }
        $parametersAcceptor = $methodReflection->getVariants()[0];
        $returnType = $parametersAcceptor->getReturnType();
        if ($returnType instanceof \PHPStan\Type\MixedType) {
            return null;
        }
        return $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($returnType, \Rector\PHPStanStaticTypeMapper\ValueObject\TypeKind::RETURN());
    }
    /**
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\NullableType|PhpParserUnionType $returnedStrictTypeNode
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure $functionLike
     * @return \PhpParser\Node\Expr\Closure|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_
     */
    private function refactorSingleReturnType(\PhpParser\Node\Stmt\Return_ $return, $returnedStrictTypeNode, $functionLike)
    {
        $resolvedType = $this->nodeTypeResolver->resolve($return);
        if ($resolvedType instanceof \PHPStan\Type\UnionType) {
            if (!$returnedStrictTypeNode instanceof \PhpParser\Node\NullableType) {
                return $functionLike;
            }
            return $this->processSingleUnionType($functionLike, $resolvedType, $returnedStrictTypeNode);
        }
        /** @var Name $returnType */
        $returnType = $resolvedType instanceof \PHPStan\Type\ObjectType ? new \PhpParser\Node\Name\FullyQualified($resolvedType->getClassName()) : $returnedStrictTypeNode;
        $functionLike->returnType = $returnType;
        return $functionLike;
    }
}
