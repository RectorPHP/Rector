<?php

declare(strict_types=1);

namespace Rector\CakePHP\Rector\Namespace_;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use Rector\CakePHP\Naming\CakePHPFullyQualifiedClassNameResolver;
use Rector\Core\PhpParser\Node\CustomNode\FileWithoutNamespace;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see https://github.com/cakephp/upgrade/blob/756410c8b7d5aff9daec3fa1fe750a3858d422ac/src/Shell/Task/AppUsesTask.php
 * @see https://github.com/cakephp/upgrade/search?q=uses&unscoped_q=uses
 *
 * @see \Rector\CakePHP\Tests\Rector\Namespace_\AppUsesStaticCallToUseStatementRector\AppUsesStaticCallToUseStatementRectorTest
 */
final class AppUsesStaticCallToUseStatementRector extends AbstractRector
{
    /**
     * @var CakePHPFullyQualifiedClassNameResolver
     */
    private $cakePHPFullyQualifiedClassNameResolver;

    public function __construct(CakePHPFullyQualifiedClassNameResolver $cakePHPFullyQualifiedClassNameResolver)
    {
        $this->cakePHPFullyQualifiedClassNameResolver = $cakePHPFullyQualifiedClassNameResolver;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Change App::uses() to use imports', [
            new CodeSample(
                <<<'CODE_SAMPLE'
App::uses('NotificationListener', 'Event');

CakeEventManager::instance()->attach(new NotificationListener());
CODE_SAMPLE
,
                <<<'CODE_SAMPLE'
use Event\NotificationListener;

CakeEventManager::instance()->attach(new NotificationListener());
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [FileWithoutNamespace::class, Namespace_::class];
    }

    /**
     * @param FileWithoutNamespace|Namespace_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $appUsesStaticCalls = $this->collectAppUseStaticCalls($node);
        if ($appUsesStaticCalls === []) {
            return null;
        }

        $this->removeNodes($appUsesStaticCalls);

        $names = $this->resolveNamesFromStaticCalls($appUsesStaticCalls);
        $uses = $this->nodeFactory->createUsesFromNames($names);

        if ($node instanceof Namespace_) {
            $node->stmts = array_merge($uses, (array) $node->stmts);
            return $node;
        }

        return $this->refactorFile($node, $uses);
    }

    private function createFullyQualifiedNameFromAppUsesStaticCall(StaticCall $staticCall): string
    {
        /** @var string $shortClassName */
        $shortClassName = $this->getValue($staticCall->args[0]->value);

        /** @var string $namespaceName */
        $namespaceName = $this->getValue($staticCall->args[1]->value);

        return $this->cakePHPFullyQualifiedClassNameResolver->resolveFromPseudoNamespaceAndShortClassName(
            $namespaceName,
            $shortClassName
        );
    }

    /**
     * @return StaticCall[]
     */
    private function collectAppUseStaticCalls(Node $node): array
    {
        /** @var StaticCall[] $appUsesStaticCalls */
        $appUsesStaticCalls = $this->betterNodeFinder->find($node, function (Node $node) {
            if (! $node instanceof StaticCall) {
                return false;
            }

            return $this->isStaticCallNamed($node, 'App', 'uses');
        });

        return $appUsesStaticCalls;
    }

    /**
     * @param Use_[] $uses
     */
    private function refactorFile(FileWithoutNamespace $file, array $uses): ?FileWithoutNamespace
    {
        $hasNamespace = $this->betterNodeFinder->findFirstInstanceOf($file, Namespace_::class);
        // already handled above
        if ($hasNamespace) {
            return null;
        }

        $hasDeclare = $this->betterNodeFinder->findFirstInstanceOf($file, Declare_::class);
        if ($hasDeclare) {
            return $this->refactorFileWithDeclare($file, $uses);
        }

        $file->stmts = array_merge($uses, (array) $file->stmts);
        return $file;
    }

    /**
     * @param Use_[] $uses
     */
    private function refactorFileWithDeclare(FileWithoutNamespace $file, array $uses): FileWithoutNamespace
    {
        $newStmts = [];
        foreach ($file->stmts as $stmt) {
            $newStmts[] = $stmt;

            if ($stmt instanceof Declare_) {
                foreach ($uses as $use) {
                    $newStmts[] = $use;
                }

                continue;
            }
        }

        return new FileWithoutNamespace($newStmts);
    }

    /**
     * @param StaticCall[] $staticCalls
     * @return string[]
     */
    private function resolveNamesFromStaticCalls(array $staticCalls): array
    {
        $names = [];
        foreach ($staticCalls as $staticCall) {
            $names[] = $this->createFullyQualifiedNameFromAppUsesStaticCall($staticCall);
        }

        return $names;
    }
}
