<?php

declare(strict_types=1);

namespace Rector\DeadCode\Rector\Assign;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use Rector\Core\Php\ReservedKeywordAnalyzer;
use PhpParser\Node\Stmt\If_;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Tests\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector\RemoveUnusedVariableAssignRectorTest
 */
final class RemoveUnusedVariableAssignRector extends AbstractRector
{
    /**
     * @var ReservedKeywordAnalyzer
     */
    private $reservedKeywordAnalyzer;

    public function __construct(ReservedKeywordAnalyzer $reservedKeywordAnalyzer)
    {
        $this->reservedKeywordAnalyzer = $reservedKeywordAnalyzer;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused assigns to variables', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $value = 5;
    }
}
CODE_SAMPLE
,
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Assign::class];
    }

    /**
     * @param Assign $node
     */
    public function refactor(Node $node): ?Node
    {
        $classMethod = $node->getAttribute(AttributeKey::METHOD_NODE);
        if (! $classMethod instanceof FunctionLike) {
            return null;
        }

        $variable = $node->var;
        if (! $variable instanceof Variable) {
            return null;
        }

        // variable is used
        if ($this->isUsed($node, $variable)) {
            $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
            $ifNode = $parentNode->getAttribute(AttributeKey::NEXT_NODE);

            // check if next node is if
            if (! $ifNode instanceof If_) {
                return null;
            }

            return $this->searchIfAndElseForVariableRedeclaration($node, $ifNode);
        }

        $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
        if (! $parentNode instanceof Expression) {
            return null;
        }

        if (is_string($variable->name) && $this->reservedKeywordAnalyzer->isNativeVariable($variable->name)) {
            return null;
        }

        $this->removeNode($node);
        return $node;
    }

    private function isUsed(Assign $assign, Variable $variable): bool
    {
        $isUsedPrev = (bool) $this->betterNodeFinder->findFirstPreviousOfNode($variable, function (Node $node) use (
            $variable
        ): bool {
            return $this->isVariableNamed($node, $variable);
        });

        if ($isUsedPrev) {
            return true;
        }

        $isUsedNext = (bool) $this->betterNodeFinder->findFirstNext($variable, function (Node $node) use (
            $variable
        ): bool {
            return $this->isVariableNamed($node, $variable);
        });

        if ($isUsedNext) {
            return true;
        }

        /** @var FuncCall|MethodCall|New_|NullsafeMethodCall|StaticCall $expr */
        $expr = $assign->expr;
        if (! $this->isCall($expr)) {
            return false;
        }

        $args = $expr->args;
        foreach ($args as $arg) {
            $variable = $arg->value;
            if (! $variable instanceof Variable) {
                continue;
            }

            $previousAssign = $this->betterNodeFinder->findFirstPreviousOfNode($assign, function (Node $node) use (
                $variable
            ): bool {
                return $node instanceof Assign && $this->isVariableNamed($node->var, $variable);
            });
            if ($previousAssign instanceof Assign) {
                return $this->isUsed($assign, $variable);
            }
        }

        return false;
    }

    private function isCall(Expr $expr): bool
    {
        return $expr instanceof FuncCall || $expr instanceof MethodCall || $expr instanceof New_ || $expr instanceof NullsafeMethodCall || $expr instanceof StaticCall;
    }

    private function isVariableNamed(Node $node, Variable $variable): bool
    {
        if ($node instanceof MethodCall && $node->name instanceof Variable && is_string($node->name->name)) {
            return $this->isName($variable, $node->name->name);
        }

        if ($node instanceof PropertyFetch && $node->name instanceof Variable && is_string($node->name->name)) {
            return $this->isName($variable, $node->name->name);
        }

        if (! $node instanceof Variable) {
            return false;
        }

        return $this->isName($variable, (string) $this->getName($node));
    }

    private function searchIfAndElseForVariableRedeclaration(Assign $node, If_ $ifNode): ?Node
    {
        /** @var Variable $varNode */
        $varNode = $node->var;

        // search if for redeclaration of variable
        /** @var Node\Stmt\Expression $statementIf */
        foreach ($ifNode->stmts as $statementIf) {
            if (! $statementIf->expr instanceof Assign) {
                continue;
            }

            /** @var Variable $varIf */
            $varIf = $statementIf->expr->var;
            if ($varNode->name !== $varIf->name) {
                continue;
            }

            $elseNode = $ifNode->else;
            if (! $elseNode instanceof Else_) {
                continue;
            }

            // search else for redeclaration of variable
            /** @var Node\Stmt\Expression $statementElse */
            foreach ($elseNode->stmts as $statementElse) {
                if (! $statementElse->expr instanceof Assign) {
                    continue;
                }

                /** @var Variable $varElse */
                $varElse = $statementElse->expr->var;
                if ($varNode->name !== $varElse->name) {
                    continue;
                }

                $this->removeNode($node);
                return $node;
            }
        }

        return null;
    }
}
