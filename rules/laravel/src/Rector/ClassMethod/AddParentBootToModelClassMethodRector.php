<?php

declare(strict_types=1);

namespace Rector\Laravel\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\Nette\NodeAnalyzer\StaticCallAnalyzer;

/**
 * @see https://laracasts.com/discuss/channels/laravel/laravel-57-upgrade-observer-problem
 *
 * @see \Rector\Laravel\Tests\Rector\ClassMethod\AddParentBootToModelClassMethodRector\AddParentBootToModelClassMethodRectorTest
 */
final class AddParentBootToModelClassMethodRector extends AbstractRector
{
    /**
     * @var StaticCallAnalyzer
     */
    private $staticCallAnalyzer;

    public function __construct(StaticCallAnalyzer $staticCallAnalyzer)
    {
        $this->staticCallAnalyzer = $staticCallAnalyzer;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Add parent::boot(); call to boot() class method in child of Illuminate\Database\Eloquent\Model',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function boot()
    {
    }
}
CODE_SAMPLE

                    ,
                    <<<'CODE_SAMPLE'
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function boot()
    {
        parent::boot();
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
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isInObjectType($node, 'Illuminate\Database\Eloquent\Model')) {
            return null;
        }

        if (! $this->isName($node->name, 'boot')) {
            return null;
        }

        foreach ((array) $node->stmts as $key => $classMethodStmt) {
            if ($classMethodStmt instanceof Expression) {
                $classMethodStmt = $classMethodStmt->expr;
            }

            // is in the 1st position? → only correct place
            // @see https://laracasts.com/discuss/channels/laravel/laravel-57-upgrade-observer-problem?page=0#reply=454409
            if (! $this->staticCallAnalyzer->isParentCallNamed($classMethodStmt, 'boot')) {
                continue;
            }

            if ($key === 0) {
                return null;
            }

            // wrong location → remove it
            unset($node->stmts[$key]);
        }

        // missing, we need to add one
        $staticCall = $this->nodeFactory->createStaticCall('parent', 'boot');
        $parentStaticCallExpression = new Expression($staticCall);

        $node->stmts = array_merge([$parentStaticCallExpression], (array) $node->stmts);

        return $node;
    }
}
