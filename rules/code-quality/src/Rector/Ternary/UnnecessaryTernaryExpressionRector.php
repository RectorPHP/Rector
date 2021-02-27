<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Rector\Ternary;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Ternary;
use PHPStan\Type\BooleanType;
use Rector\Core\PhpParser\Node\AssignAndBinaryMap;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\CodeQuality\Tests\Rector\Ternary\UnnecessaryTernaryExpressionRector\UnnecessaryTernaryExpressionRectorTest
 */
final class UnnecessaryTernaryExpressionRector extends AbstractRector
{
    /**
     * @var AssignAndBinaryMap
     */
    private $assignAndBinaryMap;

    public function __construct(AssignAndBinaryMap $assignAndBinaryMap)
    {
        $this->assignAndBinaryMap = $assignAndBinaryMap;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove unnecessary ternary expressions.',
            [new CodeSample('$foo === $bar ? true : false;', '$foo === $bar;')]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Ternary::class];
    }

    /**
     * @param Ternary $node
     */
    public function refactor(Node $node): ?Node
    {
        /** @var Ternary $ternaryExpression */
        $ternaryExpression = $node;
        if (! $ternaryExpression->if instanceof Expr) {
            return null;
        }

        $ifExpression = $ternaryExpression->if;
        if (! $this->valueResolver->isTrueOrFalse($ifExpression)) {
            return null;
        }

        $elseExpression = $ternaryExpression->else;
        if (! $this->valueResolver->isTrueOrFalse($elseExpression)) {
            return null;
        }

        $condition = $ternaryExpression->cond;
        if (! $condition instanceof BinaryOp) {
            return $this->processNonBinaryCondition($ifExpression, $elseExpression, $condition);
        }
        if ($this->valueResolver->isNull($ifExpression)) {
            return null;
        }
        if ($this->valueResolver->isNull($elseExpression)) {
            return null;
        }

        /** @var BinaryOp $binaryOperation */
        $binaryOperation = $node->cond;

        if ($this->valueResolver->isTrue($ifExpression) && $this->valueResolver->isFalse($elseExpression)) {
            return $binaryOperation;
        }

        $inversedBinaryClass = $this->assignAndBinaryMap->getInversed($binaryOperation);
        if ($inversedBinaryClass === null) {
            return null;
        }

        return new $inversedBinaryClass($binaryOperation->left, $binaryOperation->right);
    }

    private function processNonBinaryCondition(Expr $ifExpression, Expr $elseExpression, Expr $condition): ?Node
    {
        if ($this->valueResolver->isTrue($ifExpression) && $this->valueResolver->isFalse($elseExpression)) {
            if ($this->isStaticType($condition, BooleanType::class)) {
                return $condition;
            }

            return new Bool_($condition);
        }

        if ($this->valueResolver->isFalse($ifExpression) && $this->valueResolver->isTrue($elseExpression)) {
            if ($this->isStaticType($condition, BooleanType::class)) {
                return new BooleanNot($condition);
            }

            return new BooleanNot(new Bool_($condition));
        }

        return null;
    }
}
