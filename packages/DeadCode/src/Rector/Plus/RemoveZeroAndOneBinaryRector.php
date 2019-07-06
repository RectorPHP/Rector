<?php declare(strict_types=1);

namespace Rector\DeadCode\Rector\Plus;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Div;
use PhpParser\Node\Expr\BinaryOp\Minus;
use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\BinaryOp\Plus;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

/**
 * @see https://3v4l.org/I0BGs
 */
final class RemoveZeroAndOneBinaryRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $value = 5 * 1;
        $value = 5 + 0;
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $value = 5;
        $value = 5;
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
        return [
            Plus::class,
            Minus::class,
            Mul::class,
            Div::class,
            Node\Expr\AssignOp\Plus::class,
            Node\Expr\AssignOp\Minus::class,
            Node\Expr\AssignOp\Mul::class,
            Node\Expr\AssignOp\Div::class,
        ];
    }

    /**
     * @param AssignOp|BinaryOp $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof AssignOp) {
            return $this->processAssignOp($node);
        }

        // -, +
        if ($node instanceof BinaryOp) {
            return $this->processBinaryOp($node);
        }
    }

    /**
     * @param Plus|Minus $binaryOp
     */
    private function processBinaryPlusAndMinus(BinaryOp $binaryOp): ?Expr
    {
        if ($this->isValue($binaryOp->left, 0)) {
            if ($this->isNumberType($binaryOp->right)) {
                return $binaryOp->right;
            }
        }

        if ($this->isValue($binaryOp->right, 0)) {
            if ($this->isNumberType($binaryOp->left)) {
                return $binaryOp->left;
            }
        }

        return null;
    }

    /**
     * @param Mul|Div $binaryOp
     */
    private function processBinaryMulAndDiv(BinaryOp $binaryOp): ?Expr
    {
        if ($binaryOp instanceof Mul) {
            if ($this->isValue($binaryOp->left, 1)) {
                if ($this->isNumberType($binaryOp->right)) {
                    return $binaryOp->right;
                }
            }
        }

        if ($this->isValue($binaryOp->right, 1)) {
            if ($this->isNumberType($binaryOp->left)) {
                return $binaryOp->left;
            }
        }

        return null;
    }

    private function processAssignOp(Node $node): ?Expr
    {
        // +=, -=
        if ($node instanceof Node\Expr\AssignOp\Plus || $node instanceof Node\Expr\AssignOp\Minus) {
            if (! $this->isValue($node->expr, 0)) {
                return null;
            }

            if ($this->isNumberType($node->var)) {
                return $node->var;
            }
        }

        // *, /
        if ($node instanceof Node\Expr\AssignOp\Mul || $node instanceof Node\Expr\AssignOp\Div) {
            if (! $this->isValue($node->expr, 1)) {
                return null;
            }
            if ($this->isNumberType($node->var)) {
                return $node->var;
            }
        }

        return null;
    }

    private function processBinaryOp(Node $node): ?Expr
    {
        if ($node instanceof Plus || $node instanceof Minus) {
            return $this->processBinaryPlusAndMinus($node);
        }

        // *, /
        if ($node instanceof Mul || $node instanceof Div) {
            return $this->processBinaryMulAndDiv($node);
        }

        return null;
    }
}
