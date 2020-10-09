<?php

declare(strict_types=1);

namespace Rector\Legacy\Rector\FileWithoutNamespace;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use Rector\CodingStyle\Naming\ClassNaming;
use Rector\Core\PhpParser\Node\CustomNode\FileWithoutNamespace;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\Legacy\Naming\FullyQualifiedNameResolver;
use Rector\Legacy\NodeFactory\StaticMethodClassFactory;
use Rector\Legacy\ValueObject\FunctionToStaticCall;
use Symplify\SmartFileSystem\SmartFileInfo;

/**
 * @see \Rector\Legacy\Tests\Rector\FileWithoutNamespace\FunctionToStaticMethodRector\FunctionToStaticMethodRectorTest
 */
final class FunctionToStaticMethodRector extends AbstractRector
{
    /**
     * @var ClassNaming
     */
    private $classNaming;

    /**
     * @var StaticMethodClassFactory
     */
    private $staticMethodClassFactory;

    /**
     * @var FullyQualifiedNameResolver
     */
    private $fullyQualifiedNameResolver;

    public function __construct(
        ClassNaming $classNaming,
        StaticMethodClassFactory $staticMethodClassFactory,
        FullyQualifiedNameResolver $fullyQualifiedNameResolver
    ) {
        $this->classNaming = $classNaming;
        $this->staticMethodClassFactory = $staticMethodClassFactory;
        $this->fullyQualifiedNameResolver = $fullyQualifiedNameResolver;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Change functions to static calls, so composer can autoload them', [
            new CodeSample(
                <<<'CODE_SAMPLE'
function some_function()
{
}

some_function('lol');
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeUtilsClass
{
    public static function someFunction()
    {
    }
}

SomeUtilsClass::someFunction('lol');
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
        /** @var Function_[] $functions */
        $functions = $this->betterNodeFinder->findInstanceOf($node, Function_::class);
        if ($functions === []) {
            return null;
        }

        $smartFileInfo = $node->getAttribute(SmartFileInfo::class);
        if ($smartFileInfo === null) {
            return null;
        }

        $shortClassName = $this->classNaming->getNameFromFileInfo($smartFileInfo);
        $class = $this->staticMethodClassFactory->createStaticMethodClass($shortClassName, $functions);

        $stmts = $node->stmts;
        $this->removeNodes($functions);

        // replace function calls with class static call
        $functionsToStaticCalls = $this->resolveFunctionsToStaticCalls($stmts, $shortClassName, $functions);
        $node->stmts = $this->replaceFuncCallsWithStaticCalls($stmts, $functionsToStaticCalls);

        $this->printStaticMethodClass($smartFileInfo, $shortClassName, $node, $class);

        return $node;
    }

    /**
     * @param Node[] $stmts
     * @param Function_[] $functions
     * @return FunctionToStaticCall[]
     */
    private function resolveFunctionsToStaticCalls(array $stmts, string $shortClassName, array $functions): array
    {
        $functionsToStaticCalls = [];

        $className = $this->fullyQualifiedNameResolver->resolveFullyQualifiedName($stmts, $shortClassName);
        foreach ($functions as $function) {
            $functionName = $this->getName($function);
            if ($functionName === null) {
                continue;
            }

            $methodName = $this->classNaming->createMethodNameFromFunction($function);
            $functionsToStaticCalls[] = new FunctionToStaticCall($functionName, $className, $methodName);
        }

        return $functionsToStaticCalls;
    }

    /**
     * @param Node[] $stmts
     * @param FunctionToStaticCall[] $functionsToStaticCalls
     * @return Node[]
     */
    private function replaceFuncCallsWithStaticCalls(array $stmts, array $functionsToStaticCalls): array
    {
        $this->traverseNodesWithCallable($stmts, function (Node $node) use ($functionsToStaticCalls): ?StaticCall {
            if (! $node instanceof FuncCall) {
                return null;
            }

            foreach ($functionsToStaticCalls as $functionToStaticCall) {
                if (! $this->isName($node, $functionToStaticCall->getFunction())) {
                    continue;
                }

                $staticCall = $this->createStaticCall(
                    $functionToStaticCall->getClass(),
                    $functionToStaticCall->getMethod()
                );
                $staticCall->args = $node->args;

                return $staticCall;
            }

            return null;
        });

        return $stmts;
    }

    /**
     * @param Namespace_|FileWithoutNamespace $node
     */
    private function printStaticMethodClass(
        SmartFileInfo $smartFileInfo,
        string $shortClassName,
        Node $node,
        Class_ $class
    ): void {
        $classFileDestination = $smartFileInfo->getPath() . DIRECTORY_SEPARATOR . $shortClassName . '.php';

        $nodesToPrint = $this->resolveNodeToPrint($node, $class);
        $this->printNodesToFilePath($nodesToPrint, $classFileDestination);
    }

    /**
     * @param Namespace_|FileWithoutNamespace $node
     * @return Namespace_|Class_
     */
    private function resolveNodeToPrint(Node $node, Class_ $class): Node
    {
        if ($node instanceof Namespace_) {
            return new Namespace_($node->name, [$class]);
        }

        return $class;
    }
}
