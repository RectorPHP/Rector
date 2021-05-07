<?php

declare(strict_types=1);

namespace Rector\DowngradePhp70\Rector\New_;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use Rector\Core\NodeAnalyzer\ClassAnalyzer;
use Rector\Core\Rector\AbstractRector;
use Rector\DowngradePhp70\NodeFactory\ClassFromAnonymousFactory;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Tests\DowngradePhp70\Rector\New_\DowngradeAnonymousClassRector\DowngradeAnonymousClassRectorTest
 */
final class DowngradeAnonymousClassRector extends AbstractRector
{
    /**
     * @var string
     */
    private const CLASS_NAME = 'AnonymousFor_';

    /**
     * @var ClassAnalyzer
     */
    private $classAnalyzer;

    /**
     * @var ClassFromAnonymousFactory
     */
    private $classFromAnonymousFactory;

    public function __construct(
        ClassAnalyzer $classAnalyzer,
        ClassFromAnonymousFactory $classFromAnonymousFactory
    ) {
        $this->classAnalyzer = $classAnalyzer;
        $this->classFromAnonymousFactory = $classFromAnonymousFactory;
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [New_::class];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Remove anonymous class',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        return new class {
            public function execute()
            {
            }
        };
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
class Anonymous
{
    public function execute()
    {
    }
}
class SomeClass
{
    public function run()
    {
        return new Anonymous();
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @param New_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->classAnalyzer->isAnonymousClass($node->class)) {
            return null;
        }

        $classLike = $this->betterNodeFinder->findParentType($node, ClassLike::class);
        if ($classLike instanceof ClassLike) {
            return $this->processMoveAnonymousClassInClass($node, $classLike);
        }

        $functionNode = $this->betterNodeFinder->findParentType($node, Function_::class);
        if ($functionNode instanceof Function_) {
            return $this->processMoveAnonymousClassInFunction($node, $functionNode);
        }

        $statement = $node->getAttribute(AttributeKey::CURRENT_STATEMENT);
        return $this->processMoveAnonymousClassInDirectCall($node, $statement);
    }

    private function getNamespacedClassName(string $namespace, string $className): string
    {
        return $namespace === ''
            ? $className
            : $namespace . '\\' . $className;
    }

    private function getClassName(string $namespace, string $shortName): string
    {
        $className = self::CLASS_NAME . $shortName;
        $namespacedClassName = $this->getNamespacedClassName($namespace, $className);

        $count = 0;
        while ($this->nodeRepository->findClass($namespacedClassName)) {
            $className .= ++$count;
            $namespacedClassName = $this->getNamespacedClassName($namespace, $className);
        }

        return ucfirst($className);
    }

    private function processMoveAnonymousClassInClass(New_ $new, ClassLike $classLike): ?New_
    {
        $namespacedClassName = $this->getName($classLike->namespacedName);

        $shortClassName = $this->nodeNameResolver->getShortName($classLike->name);

        $namespace = $namespacedClassName === $shortClassName
            ? ''
            : Strings::substring($namespacedClassName, 0, - strlen($shortClassName) - 1);
        $className = $this->getClassName($namespace, $shortClassName);

        return $this->processMove($new, $className, $classLike);
    }

    private function processMoveAnonymousClassInFunction(New_ $new, Function_ $function): ?New_
    {
        $namespacedFunctionName = (string) $this->getName($function);
        $shortFunctionName = (string) $this->getName($function->name);
        $namespace = $namespacedFunctionName === $shortFunctionName
            ? ''
            : Strings::substring($namespacedFunctionName, 0, - strlen($shortFunctionName) - 1);
        $className = $this->getClassName($namespace, $shortFunctionName);

        return $this->processMove($new, $className, $function);
    }

    private function processMoveAnonymousClassInDirectCall(New_ $new, Stmt $stmt): ?New_
    {
        $parent = $stmt->getAttribute(AttributeKey::PARENT_NODE);
        while ($parent instanceof Node && ! $parent instanceof Namespace_) {
            $parent = $parent->getAttribute(AttributeKey::PARENT_NODE);
        }

        $namespace = $parent instanceof Namespace_
            ? (string) $this->getName($parent)
            : '';
        $suffix = $namespace === ''
            ? 'NotInfunctionNoNamespace'
            : 'NotInFunction';

        $className = $this->getClassName($namespace, $suffix);

        return $this->processMove($new, $className, $stmt);
    }

    private function processMove(New_ $new, string $className, Node $node): ?New_
    {
        if (! $new->class instanceof Class_) {
            return null;
        }

        $class = $this->classFromAnonymousFactory->create($className, $new->class);

        $currentClass = $node->getAttribute(AttributeKey::CLASS_NODE);
        if ($currentClass instanceof ClassLike) {
            $this->addNodeBeforeNode($class, $currentClass);
        } else {
            $this->addNodeBeforeNode($class, $node);
        }

        return new New_(new Name($className), $new->args);
    }
}
