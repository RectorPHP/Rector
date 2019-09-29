<?php declare(strict_types=1);

namespace Rector\Rector;

use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;
use Rector\Contract\Rector\PhpRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Php\PhpVersionProvider;
use Rector\Rector\AbstractRector\AbstractRectorTrait;
use Rector\Rector\AbstractRector\NodeCommandersTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\FileSystem\SmartFileInfo;

abstract class AbstractRector extends NodeVisitorAbstract implements PhpRectorInterface
{
    use AbstractRectorTrait;

    /**
     * @var BuilderFactory
     */
    protected $builderFactory;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var PhpVersionProvider
     */
    private $phpVersionProvider;

    /**
     * Run once in the every end of one processed file
     */
    protected function tearDown(): void
    {
    }

    /**
     * @required
     */
    public function autowireAbstractRectorDependencies(
        SymfonyStyle $symfonyStyle,
        PhpVersionProvider $phpVersionProvider,
        BuilderFactory $builderFactory
    ): void {
        $this->symfonyStyle = $symfonyStyle;
        $this->phpVersionProvider = $phpVersionProvider;
        $this->builderFactory = $builderFactory;
    }

    /**
     * @return int|Node|null
     */
    final public function enterNode(Node $node)
    {
        if (! $this->isMatchingNodeType(get_class($node))) {
            return null;
        }

        // show current Rector class on --debug
        if ($this->symfonyStyle->isDebug()) {
            $this->symfonyStyle->writeln('[applying] ' . static::class);
        }

        // already removed
        if ($this->isNodeRemoved($node)) {
            return null;
        }

        // node should be ignore
        if ($this->shouldIgnoreRectorForNode($node)) {
            return null;
        }

        $originalNode = $node->getAttribute(AttributeKey::ORIGINAL_NODE) ?? clone $node;
        $originalNodeWithAttributes = clone $node;
        $originalComment = $node->getComments();
        $originalDocComment = $node->getDocComment();
        $node = $this->refactor($node);

        // nothing to change → continue
        if ($node === null) {
            return null;
        }

        // changed!
        if ($this->hasNodeChanged($originalNode, $node)) {
            $this->mirrorAttributes($originalNodeWithAttributes, $node);
            $this->updateAttributes($node);
            $this->keepFileInfoAttribute($node, $originalNode);
            $this->notifyNodeChangeFileInfo($node);

        // doc block has changed
        } elseif ($node->getComments() !== $originalComment || $node->getDocComment() !== $originalDocComment) {
            $this->notifyNodeChangeFileInfo($node);
        }

        // if stmt ("$value;") was replaced by expr ("$value"), add the ending ";" (Expression) to prevent breaking the code
        if ($originalNode instanceof Stmt && $node instanceof Expr) {
            return new Expression($node);
        }

        return $node;
    }

    /**
     * @see NodeCommandersTrait
     *
     * @param Node[] $nodes
     * @return Node[]
     */
    public function afterTraverse(array $nodes): array
    {
        if ($this->nodeAddingCommander->isActive()) {
            $nodes = $this->nodeAddingCommander->traverseNodes($nodes);
        }

        if ($this->propertyAddingCommander->isActive()) {
            $nodes = $this->propertyAddingCommander->traverseNodes($nodes);
        }

        if ($this->nodeRemovingCommander->isActive()) {
            $nodes = $this->nodeRemovingCommander->traverseNodes($nodes);
        }

        // this must run before use imports, since it adds them
        if ($this->nameImportingCommander->isActive()) {
            $nodes = $this->nameImportingCommander->traverseNodes($nodes);
        }

        if ($this->useAddingCommander->isActive()) {
            $nodes = $this->useAddingCommander->traverseNodes($nodes);
        }

        // @todo class like renaming

        $this->tearDown();

        return $nodes;
    }

    protected function shouldIgnoreRectorForNode(Node $node): bool
    {
        $comment = $node->getDocComment();
        if ($comment !== null && $this->checkCommentForIgnore($comment)) {
            return true;
        }

        // recurse up until a Stmt node is found since it might contain a noRector
        if (! $node instanceof Stmt && $node->getAttribute(AttributeKey::PARENT_NODE)) {
            return $this->shouldIgnoreRectorForNode($node->getAttribute(AttributeKey::PARENT_NODE));
        }

        return false;
    }

    protected function getNextExpression(Node $node): ?Node
    {
        $currentExpression = $node->getAttribute(AttributeKey::CURRENT_EXPRESSION);
        if (! $currentExpression instanceof Expression) {
            return null;
        }

        return $currentExpression->getAttribute(AttributeKey::NEXT_NODE);
    }

    /**
     * @param Expr[]|null[] $nodes
     * @param mixed[] $expectedValues
     */
    protected function areValues(array $nodes, array $expectedValues): bool
    {
        foreach ($nodes as $i => $node) {
            if ($node !== null && $this->isValue($node, $expectedValues[$i])) {
                continue;
            }

            return false;
        }

        return true;
    }

    protected function isAtLeastPhpVersion(string $version): bool
    {
        return $this->phpVersionProvider->isAtLeast($version);
    }

    protected function checkCommentForIgnore(Doc $doc): bool
    {
        if (preg_match_all('#@noRector\s*(?<rectorName>\S+)#i', $doc->getText(), $matches)) {
            foreach ($matches['rectorName'] as $ignoreSpec) {
                if (static::class === ltrim($ignoreSpec, '\\')) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isMatchingNodeType(string $nodeClass): bool
    {
        foreach ($this->getNodeTypes() as $nodeType) {
            if (is_a($nodeClass, $nodeType, true)) {
                return true;
            }
        }

        return false;
    }

    private function keepFileInfoAttribute(Node $node, Node $originalNode): void
    {
        if ($node->getAttribute(AttributeKey::FILE_INFO) instanceof SmartFileInfo) {
            return;
        }

        if ($originalNode->getAttribute(AttributeKey::FILE_INFO) !== null) {
            $node->setAttribute(AttributeKey::FILE_INFO, $originalNode->getAttribute(AttributeKey::FILE_INFO));
        } elseif ($originalNode->getAttribute(AttributeKey::PARENT_NODE)) {
            /** @var Node $parentOriginalNode */
            $parentOriginalNode = $originalNode->getAttribute(AttributeKey::PARENT_NODE);
            $node->setAttribute(AttributeKey::FILE_INFO, $parentOriginalNode->getAttribute(AttributeKey::FILE_INFO));
        }
    }

    private function mirrorAttributes(Node $oldNode, Node $newNode): void
    {
        $attributesToMirror = [
            AttributeKey::PARENT_NODE,
            AttributeKey::CLASS_NODE,
            AttributeKey::CLASS_NAME,
            AttributeKey::FILE_INFO,
            AttributeKey::METHOD_NODE,
            AttributeKey::USE_NODES,
            AttributeKey::SCOPE,
            AttributeKey::METHOD_NAME,
            AttributeKey::NAMESPACE_NAME,
            AttributeKey::NAMESPACE_NODE,
            AttributeKey::RESOLVED_NAME,
        ];

        foreach ($oldNode->getAttributes() as $attributeName => $oldNodeAttributeValue) {
            if (! in_array($attributeName, $attributesToMirror, true)) {
                continue;
            }

            $newNode->setAttribute($attributeName, $oldNodeAttributeValue);
        }
    }

    private function updateAttributes(Node $node): void
    {
        // update Resolved name attribute if name is changed
        if ($node instanceof Name) {
            $node->setAttribute(AttributeKey::RESOLVED_NAME, $node->toString());
        }
    }

    private function hasNodeChanged(Node $originalNode, Node $node): bool
    {
        return ! $this->areNodesEqual($originalNode, $node);
    }
}
