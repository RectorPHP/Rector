<?php declare(strict_types=1);

namespace Rector\Rector\Contrib\PHPUnit\SpecificMethod;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\String_;
use Rector\NodeAnalyzer\MethodCallAnalyzer;
use Rector\NodeChanger\IdentifierRenamer;
use Rector\Rector\AbstractRector;

/**
 * Before:
 * - $this->assertTrue(isset($anything->foo));
 * - $this->assertFalse(isset($anything->foo));
 *
 * After:
 * - $this->assertObjectHasAttribute('foo', $anything);
 * - $this->assertObjectNotHasAttribute('foo', $anything);
 */
final class AssertTrueIssetToObjectHasAttributeRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private $renameMethodsMap = [
        'assertTrue' => 'assertObjectHasAttribute',
        'assertFalse' => 'assertObjectNotHasAttribute',
    ];

    /**
     * @var MethodCallAnalyzer
     */
    private $methodCallAnalyzer;

    /**
     * @var IdentifierRenamer
     */
    private $identifierRenamer;

    public function __construct(MethodCallAnalyzer $methodCallAnalyzer, IdentifierRenamer $identifierRenamer)
    {
        $this->methodCallAnalyzer = $methodCallAnalyzer;
        $this->identifierRenamer = $identifierRenamer;
    }

    public function isCandidate(Node $node): bool
    {
        if (! $this->methodCallAnalyzer->isTypesAndMethods(
            $node,
            ['PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase'],
            array_keys($this->renameMethodsMap)
        )) {
            return false;
        }

        $methodCallNode = $node;

        $firstArgumentValue = $methodCallNode->args[0]->value;

        // is property access
        if (! $firstArgumentValue instanceof Isset_) {
            return false;
        }

        $issetNode = $firstArgumentValue;

        return $issetNode->vars[0] instanceof PropertyFetch;
    }

    /**
     * @param MethodCall $methodCallNode
     */
    public function refactor(Node $methodCallNode): ?Node
    {
        // rename method
        $this->identifierRenamer->renameNodeWithMap($methodCallNode, $this->renameMethodsMap);

        /** @var Isset_ $issetNode */
        $issetNode = $methodCallNode->args[0]->value;

        /** @var PropertyFetch $propertyFetchNode */
        $propertyFetchNode = $issetNode->vars[0];

        // and set as arguments
        $methodCallNode->args = [
            new Arg(new String_($propertyFetchNode->name->toString())),
            new Arg($propertyFetchNode->var),
        ];

        return $methodCallNode;
    }
}
