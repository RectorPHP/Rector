<?php

declare(strict_types=1);

namespace Rector\DeadCode\NodeCollector;

use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\NodeManipulator\ClassMethodManipulator;
use Rector\Core\ValueObject\MethodName;
use Rector\NodeNameResolver\NodeNameResolver;

final class UnusedParameterResolver
{
    /**
     * @var ClassMethodManipulator
     */
    private $classMethodManipulator;

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    public function __construct(
        ClassMethodManipulator $classMethodManipulator,
        NodeNameResolver $nodeNameResolver
    ) {
        $this->classMethodManipulator = $classMethodManipulator;
        $this->nodeNameResolver = $nodeNameResolver;
    }

    /**
     * @return Param[]
     */
    public function resolve(ClassMethod $classMethod): array
    {
        foreach ($classMethod->params as $i => $param) {
            // skip property promotion
            /** @var Param $param */
            if ($param->flags !== 0) {
                continue;
            }

            if ($this->classMethodManipulator->isParameterUsedInClassMethod($param, $classMethod)) {
                // reset to keep order of removed arguments, if not construtctor - probably autowired
                if (! $this->nodeNameResolver->isName($classMethod, MethodName::CONSTRUCT)) {
                    $unusedParameters = [];
                }

                continue;
            }

            $unusedParameters[$i] = $param;
        }

        return $unusedParameters;
    }
}
