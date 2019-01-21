<?php declare(strict_types=1);

namespace Rector\PhpParser\Node\Maintainer;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Return_;
use Rector\NodeTypeResolver\Node\Attribute;
use Rector\NodeTypeResolver\NodeTypeAnalyzer;
use Rector\NodeTypeResolver\Php\ReturnTypeInfo;
use Rector\PhpParser\Node\BetterNodeFinder;

final class FunctionLikeMaintainer
{
    /**
     * @var BetterNodeFinder
     */
    private $betterNodeFinder;

    /**
     * @var NodeTypeAnalyzer
     */
    private $nodeTypeAnalyzer;

    public function __construct(BetterNodeFinder $betterNodeFinder, NodeTypeAnalyzer $nodeTypeAnalyzer)
    {
        $this->betterNodeFinder = $betterNodeFinder;
        $this->nodeTypeAnalyzer = $nodeTypeAnalyzer;
    }

    /**
     * Based on static analysis of code, looking for return types
     * @param ClassMethod|Function_ $node
     */
    public function resolveStaticReturnTypeInfo(Node $node): ?ReturnTypeInfo
    {
        $classNode = $node->getAttribute(Attribute::CLASS_NODE);
        // only class or trait method body can be analyzed for returns
        if ($classNode instanceof Interface_) {
            return null;
        }

        /** @var Return_[] $returnNodes */
        $returnNodes = $this->betterNodeFinder->findInstanceOf((array) $node->stmts, Return_::class);

        $isVoid = true;

        $types = [];
        foreach ($returnNodes as $returnNode) {
            if ($returnNode->expr === null) {
                continue;
            }

            $types = array_merge($types, $this->nodeTypeAnalyzer->resolveSingleTypeToStrings($returnNode->expr));
            $isVoid = false;
        }

        if ($isVoid) {
            return new ReturnTypeInfo(['void']);
        }

        $types = array_filter($types);

        return new ReturnTypeInfo($types);
    }
}
