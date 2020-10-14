<?php

declare(strict_types=1);

namespace Rector\Core\PhpParser\Builder;

use PhpParser\Builder\Namespace_;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_ as NamespaceStmt;

/**
 * Fixed duplicated naming in php-parser and prevents confusion
 */
final class NamespaceBuilder extends Namespace_
{
    /**
     * @return NamespaceStmt
     */
    public function getNode(): Node
    {
        return parent::getNode();
    }
}
