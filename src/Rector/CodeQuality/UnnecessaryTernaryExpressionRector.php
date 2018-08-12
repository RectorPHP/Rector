<?php declare(strict_types=1);

namespace Rector\Rector\CodeQuality;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Identifier;
use Rector\Exception\NotImplementedException;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class UnnecessaryTernaryExpressionRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $inverseOperandMap = [
        Identical::class => NotIdentical::class,
        NotIdentical::class => Identical::class,
        Equal::class => NotEqual::class,
        NotEqual::class => Equal::class,
        Greater::class => Smaller::class,
        Smaller::class => Greater::class,
        GreaterOrEqual::class => SmallerOrEqual::class,
        SmallerOrEqual::class => GreaterOrEqual::class,
    ];

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Remove unnecessary ternary expressions.',
            [new CodeSample('$foo === $bar ? true : false;', '$foo === $bar;')]
        );
    }

    public function getNodeType(): string
    {
        return Ternary::class;
    }

    /** @todo remove */
    public function isCandidate(Node $node): bool
    {
        return true;
    }

    /**
     * @param Ternary $ternaryNode
     */
    public function refactor(Node $ternaryNode): ?Node
    {
        if (! $ternaryNode->if instanceof Expr) {
            return null;
        }

        $condition = $ternaryNode->cond;
        if (! $condition instanceof BinaryOp) {
            return null;
        }

        $ifExpression = $ternaryNode->if;
        $elseExpression = $ternaryNode->else;

        if (! $ifExpression instanceof ConstFetch || ! $elseExpression instanceof ConstFetch) {
            return null;
        }

        /** @var Identifier $ifExpressionName */
        $ifExpressionName = $ifExpression->name;
        /** @var Identifier $elseExpressionName */
        $elseExpressionName = $elseExpression->name;

        $ifValue = $ifExpressionName->toLowerString();
        $elseValue = $elseExpressionName->toLowerString();

        if (in_array('null', [$ifValue, $elseValue], true)) {
            return null;
        }

        if ($ifValue === 'true' && $elseValue === 'false') {
            return $condition;
        }

        return $this->inverseBinaryOperation($condition);
    }

    private function inverseBinaryOperation(BinaryOp $operation): BinaryOp
    {
        $this->ensureBinaryOperationIsSupported($operation);

        $binaryOpClassName = $this->inverseOperandMap[get_class($operation)];
        return new $binaryOpClassName($operation->left, $operation->right);
    }

    private function ensureBinaryOperationIsSupported(BinaryOp $operation): void
    {
        if (isset($this->inverseOperandMap[get_class($operation)])) {
            return;
        }

        throw new NotImplementedException(sprintf(
            '"%s" type is not implemented yet. Add it in %s',
            get_class($operation),
            __METHOD__
        ));
    }
}
