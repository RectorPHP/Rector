<?php

declare(strict_types=1);

namespace Rector\MagicDisclosure\Tests\NodeFactory\FluentChainMethodCallRootExtractor\Source;

final class SomeClassWithFluentMethods
{
    /**
     * @return self
     */
    public function one()
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function two()
    {
        return $this;
    }
}
