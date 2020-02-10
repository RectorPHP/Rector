<?php

declare(strict_types=1);

namespace Rector\Guard;

use PhpParser\Node;
use Rector\Exception\NoRectorsLoadedException;
use Rector\Exception\Rector\InvalidRectorException;
use Rector\FileSystemRector\FileSystemFileProcessor;
use Rector\PhpParser\NodeTraverser\RectorNodeTraverser;

final class RectorGuard
{
    /**
     * @var RectorNodeTraverser
     */
    private $rectorNodeTraverser;

    /**
     * @var FileSystemFileProcessor
     */
    private $fileSystemFileProcessor;

    public function __construct(
        RectorNodeTraverser $rectorNodeTraverser,
        FileSystemFileProcessor $fileSystemFileProcessor
    ) {
        $this->rectorNodeTraverser = $rectorNodeTraverser;
        $this->fileSystemFileProcessor = $fileSystemFileProcessor;
    }

    public function ensureSomeRectorsAreRegistered(): void
    {
        if ($this->rectorNodeTraverser->getPhpRectorCount() > 0) {
            return;
        }

        if ($this->fileSystemFileProcessor->getFileSystemRectorsCount() > 0) {
            return;
        }

        throw new NoRectorsLoadedException(sprintf(
            'We need some rectors to run:%s* register them in rector.yaml under "services:"%s* use "--set <set>"%s* or use "--config <file>.yaml"',
            PHP_EOL,
            PHP_EOL,
            PHP_EOL
        ));
    }

    public function ensureGetNodeTypesAreNodes(): void
    {
        foreach ($this->rectorNodeTraverser->getAllPhpRectors() as $phpRector) {
            foreach ($phpRector->getNodeTypes() as $nodeTypeClass) {
                if (is_a($nodeTypeClass, Node::class, true)) {
                    continue;
                }

                throw new InvalidRectorException(sprintf(
                    'Method "%s::getNodeTypes() provides invalid node class "%s". It must be child of "%s"',
                    get_class($phpRector),
                    $nodeTypeClass,
                    Node::class
                ));
            }
        }
    }
}
