<?php

declare(strict_types=1);

namespace Rector\Php72\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\UnionType;
use Rector\AbstractRector\Rector\AbstractConvertToAnonymousFunctionRector;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\Core\PhpParser\Parser\InlineCodeParser;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see https://stackoverflow.com/q/48161526/1348344
 * @see http://php.net/manual/en/migration72.deprecated.php#migration72.deprecated.create_function-function
 *
 * @see \Rector\Php72\Tests\Rector\FuncCall\CreateFunctionToAnonymousFunctionRector\CreateFunctionToAnonymousFunctionRectorTest
 */
final class CreateFunctionToAnonymousFunctionRector extends AbstractConvertToAnonymousFunctionRector
{
    /**
     * @var InlineCodeParser
     */
    private $inlineCodeParser;

    public function __construct(InlineCodeParser $inlineCodeParser)
    {
        $this->inlineCodeParser = $inlineCodeParser;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Use anonymous functions instead of deprecated create_function()', [
            new CodeSample(
                <<<'PHP'
class ClassWithCreateFunction
{
    public function run()
    {
        $callable = create_function('$matches', "return '$delimiter' . strtolower(\$matches[1]);");
    }
}
PHP
                ,
                <<<'PHP'
class ClassWithCreateFunction
{
    public function run()
    {
        $callable = function($matches) use ($delimiter) {
            return $delimiter . strtolower($matches[1]);
        };
    }
}
PHP
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    protected function shouldSkip(Node $node): bool
    {
        return ! $this->isName($node, 'create_function');
    }

    /**
     * @param FuncCall $node
     * @return Variable[]
     */
    protected function getParameters(Node $node): array
    {
        /** @var Variable[] */
        return $this->parseStringToParameters($node->args[0]->value);
    }

    /**
     * @return Identifier|Name|NullableType|UnionType|null
     */
    protected function getReturnType(Node $node): ?Node
    {
        return null;
    }

    /**
     * @param FuncCall $node
     * @return Stmt[]
     */
    protected function getBody(Node $node): array
    {
        return $this->parseStringToBody($node->args[1]->value);
    }

    /**
     * @return Param[]
     */
    private function parseStringToParameters(Expr $expr): array
    {
        $content = $this->inlineCodeParser->stringify($expr);
        $content = '<?php $value = function(' . $content . ') {};';

        $nodes = $this->inlineCodeParser->parse($content);

        /** @var Expression $expression */
        $expression = $nodes[0];

        /** @var Assign $assign */
        $assign = $expression->expr;

        /** @var Closure $function */
        $function = $assign->expr;
        if (! $function instanceof Closure) {
            throw new ShouldNotHappenException();
        }

        return $function->params;
    }

    /**
     * @param String_|Expr $node
     * @return Expression[]|Stmt[]
     */
    private function parseStringToBody(Node $node): array
    {
        if (! $node instanceof String_ && ! $node instanceof Encapsed && ! $node instanceof Concat) {
            // special case of code elsewhere
            return [$this->createEval($node)];
        }

        $node = $this->inlineCodeParser->stringify($node);
        return $this->inlineCodeParser->parse($node);
    }

    private function createEval(Expr $expr): Expression
    {
        $evalFuncCall = new FuncCall(new Name('eval'), [new Arg($expr)]);

        return new Expression($evalFuncCall);
    }
}
