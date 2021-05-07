<?php

declare(strict_types=1);

namespace Rector\DowngradePhp70\Rector\Coalesce;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Tests\DowngradePhp70\Rector\Coalesce\DowngradeNullCoalesceRector\DowngradeNullCoalesceRectorTest
 */
final class DowngradeNullCoalesceRector extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Coalesce::class];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Change null coalesce to isset ternary check',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
$username = $_GET['user'] ?? 'nobody';
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$username = isset($_GET['user']) ? $_GET['user'] : 'nobody';
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @param Coalesce $node
     */
    public function refactor(Node $node): ?Node
    {
        $if = $node->left;
        $else = $node->right;

        if ($if instanceof Variable || $if instanceof Node\Expr\ArrayDimFetch) {
            $cond = new Isset_([$if]);
        } else {
            $cond = new NotIdentical($if, $this->nodeFactory->createNull());
        }

        return new Ternary($cond, $if, $else);
    }
}
