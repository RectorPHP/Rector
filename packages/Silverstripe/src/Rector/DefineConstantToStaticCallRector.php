<?php

declare(strict_types=1);

namespace Rector\Silverstripe\Rector;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see \Rector\Silverstripe\Tests\Rector\DefineConstantToStaticCallRector\DefineConstantToStaticCallRectorTest
 */
final class DefineConstantToStaticCallRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Turns defined function call to static method call.', [
            new CodeSample('defined("SS_DATABASE_NAME");', 'Environment::getEnv("SS_DATABASE_NAME");'),
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
    public function refactor(Node $node): ?Node
    {
        if (count($node->args) !== 1) {
            return null;
        }

        if (! $this->isName($node, 'defined')) {
            return null;
        }

        $argumentValue = $node->args[0]->value;
        if (! $argumentValue instanceof String_) {
            return null;
        }

        if (! Strings::startsWith($argumentValue->value, 'SS_')) {
            return null;
        }

        return $this->createStaticCall('Environment', 'getEnv', $node->args);
    }
}
