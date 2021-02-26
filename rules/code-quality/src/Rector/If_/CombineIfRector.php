<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Rector\If_;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Stmt\If_;
use Rector\BetterPhpDocParser\Comment\CommentsMerger;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\CodeQuality\Tests\Rector\If_\CombineIfRector\CombineIfRectorTest
 */
final class CombineIfRector extends AbstractRector
{
    /**
     * @var CommentsMerger
     */
    private $commentsMerger;

    public function __construct(CommentsMerger $commentsMerger)
    {
        $this->commentsMerger = $commentsMerger;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Merges nested if statements', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        if ($cond1) {
            if ($cond2) {
                return 'foo';
            }
        }
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        if ($cond1 && $cond2) {
            return 'foo';
        }
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<\PhpParser\Node>>
     */
    public function getNodeTypes(): array
    {
        return [If_::class];
    }

    /**
     * @param If_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }

        /** @var If_ $subIf */
        $subIf = $node->stmts[0];
        $node->cond = new BooleanAnd($node->cond, $subIf->cond);
        $node->stmts = $subIf->stmts;

        $this->commentsMerger->keepComments($node, [$subIf]);

        return $node;
    }

    private function shouldSkip(If_ $if): bool
    {
        if ($if->else !== null) {
            return true;
        }

        if (count($if->stmts) !== 1) {
            return true;
        }

        if ($if->elseifs !== []) {
            return true;
        }

        if (! $if->stmts[0] instanceof If_) {
            return true;
        }

        if ($if->stmts[0]->else !== null) {
            return true;
        }

        return (bool) $if->stmts[0]->elseifs;
    }
}
