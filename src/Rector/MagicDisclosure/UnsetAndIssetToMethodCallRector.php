<?php

declare(strict_types=1);

namespace Rector\Core\Rector\MagicDisclosure;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Stmt\Unset_;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\ConfiguredCodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see \Rector\Core\Tests\Rector\MagicDisclosure\UnsetAndIssetToMethodCallRector\UnsetAndIssetToMethodCallRectorTest
 */
final class UnsetAndIssetToMethodCallRector extends AbstractRector
{
    /**
     * @var string[][]
     */
    private $typeToMethodCalls = [];

    /**
     * Type to method call()
     *
     * @param string[][] $typeToMethodCalls
     */
    public function __construct(array $typeToMethodCalls = [])
    {
        $this->typeToMethodCalls = $typeToMethodCalls;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Turns defined `__isset`/`__unset` calls to specific method calls.', [
            new ConfiguredCodeSample(
<<<'PHP'
$container = new SomeContainer;
isset($container["someKey"]);
PHP
                ,
                <<<'PHP'
$container = new SomeContainer;
$container->hasService("someKey");
PHP
                ,
                [
                    'SomeContainer' => [
                        'isset' => 'hasService',
                    ],
                ]
            ),
            new ConfiguredCodeSample(
                <<<'PHP'
$container = new SomeContainer;
unset($container["someKey"]);
PHP
                ,
                <<<'PHP'
$container = new SomeContainer;
$container->removeService("someKey");
PHP
                ,
                [
                    'SomeContainer' => [
                        'unset' => 'removeService',
                    ],
                ]
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Isset_::class, Unset_::class];
    }

    /**
     * @param Isset_|Unset_ $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($node->vars as $arrayDimFetchNode) {
            if (! $arrayDimFetchNode instanceof ArrayDimFetch) {
                continue;
            }

            foreach ($this->typeToMethodCalls as $type => $transformation) {
                if (! $this->isObjectType($arrayDimFetchNode, $type)) {
                    continue;
                }

                $newNode = $this->processArrayDimFetchNode($node, $arrayDimFetchNode, $transformation);
                if ($newNode !== null) {
                    return $newNode;
                }
            }
        }

        return null;
    }

    /**
     * @param string[] $methodsNamesByType
     */
    private function processArrayDimFetchNode(
        Node $node,
        ArrayDimFetch $arrayDimFetch,
        array $methodsNamesByType
    ): ?Node {
        if ($node instanceof Isset_) {
            if (! isset($methodsNamesByType['isset'])) {
                return null;
            }

            return $this->createMethodCall(
                $arrayDimFetch->var,
                $methodsNamesByType['isset'],
                [$arrayDimFetch->dim]
            );
        }

        if ($node instanceof Unset_) {
            if (! isset($methodsNamesByType['unset'])) {
                return null;
            }

            return $this->createMethodCall(
                $arrayDimFetch->var,
                $methodsNamesByType['unset'],
                [$arrayDimFetch->dim]
            );
        }

        return null;
    }
}
