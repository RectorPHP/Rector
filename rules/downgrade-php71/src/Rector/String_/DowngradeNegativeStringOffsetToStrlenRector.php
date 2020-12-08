<?php

declare(strict_types=1);

namespace Rector\DowngradePhp71\Rector\String_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Minus;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\DowngradePhp71\Tests\Rector\String_\DowngradeNegativeStringOffsetToStrlenRector\DowngradeNegativeStringOffsetToStrlenRectorTest
 */
final class DowngradeNegativeStringOffsetToStrlenRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Downgrade negative string offset to strlen',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
"abcdef"[-2];
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
"abcdef"[strlen("abcdef")-2];
CODE_SAMPLE
                    ,
                ),
            ]
        );
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [String_::class, FuncCall::class];
    }

    /**
     * @param String_|FuncCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof String_) {
            return $this->processForString($node);
        }

        if ($node instanceof FuncCall) {
            return $this->processForFuncCall($node);
        }

        return null;
    }

    private function processForString(String_ $string): ?String_
    {
        $nextNode = $string->getAttribute(AttributeKey::NEXT_NODE);
        if (! $nextNode instanceof UnaryMinus) {
            return null;
        }

        $parentOfNextNode = $nextNode->getAttribute(AttributeKey::PARENT_NODE);
        if (! $parentOfNextNode instanceof ArrayDimFetch || ! $this->areNodesEqual($parentOfNextNode->dim, $nextNode)) {
            return null;
        }

        /** @var UnaryMinus $dim */
        $dim = $parentOfNextNode->dim;
        $parentOfNextNode->dim = new Minus(
            new FuncCall(new Name('strlen'), [new Arg($string)]),
            $dim->expr
        );

        return $string;
    }

    private function processForFuncCall(FuncCall $funcCall): ?FuncCall
    {
        $name = $this->getName($funcCall);
        if ($name !== 'strpos') {
            return null;
        }

        $args = $funcCall->args;
        if (! isset($args[2])) {
            return null;
        }

        if (! $args[2]->value instanceof UnaryMinus) {
            return null;
        }

        $funcCall->args[2]->value = new Minus(
            new FuncCall(new Name('strlen'), [new Arg($args[0]->value)]),
            $args[2]->value->expr
        );

        return $funcCall;
    }
}
