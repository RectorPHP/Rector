<?php declare(strict_types=1);

namespace Rector\BetterReflection\Reflector;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\Self_;
use phpDocumentor\Reflection\Types\Static_;
use Rector\BetterReflection\Reflection\ReflectionMethod;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;

final class MethodReflector
{
    /**
     * @var SmartClassReflector
     */
    private $smartClassReflector;

    public function __construct(SmartClassReflector $smartClassReflector)
    {
        $this->smartClassReflector = $smartClassReflector;
    }

    public function reflectClassMethod(string $class, string $method): ?ReflectionMethod
    {
        try {
            $classReflection = $this->smartClassReflector->reflect($class);
        } catch (IdentifierNotFound $identifierNotFoundException) {
            return null;
        }

        if ($classReflection === null) {
            return null;
        }

        return $classReflection->getImmediateMethods()[$method] ?? null;
    }

    /**
     * @todo possibly cache, quite slow
     * @return string[]
     */
    public function getMethodReturnTypes(string $class, string $methodCallName): array
    {
        $methodReflection = $this->reflectClassMethod($class, $methodCallName);
        if ($methodReflection === null) {
            return [];
        }

        $returnType = $methodReflection->getReturnType();
        if ($returnType !== null) {
            return [(string) $returnType];
        }

        return $this->resolveDocBlockReturnTypes($class, $methodReflection->getDocBlockReturnTypes());
    }

    /**
     * @param string[] $types
     * @return string[]
     */
    public function resolveReturnTypesForTypesAndMethod(array $types, string $method): array
    {
        if (count($types) === 0) {
            return [];
        }

        $returnTypes = $this->resolveFirstMatchingTypeAndMethod($types, $method);
        if (count($returnTypes) === 0) {
            return [];
        }

        if ($returnTypes[0] === $types[0]) { // self/static
            return $types;
        }

        return $returnTypes;
    }

    /**
     * @param string[]|Type[] $returnTypes
     * @return string[]
     */
    private function resolveDocBlockReturnTypes(string $class, array $returnTypes): array
    {
        if (! isset($returnTypes[0])) {
            return [];
        }

        $types = [];
        foreach ($returnTypes as $returnType) {
            if ($returnType instanceof Object_) {
                $types[] = ltrim((string) $returnType->getFqsen(), '\\');
            }

            if ($returnType instanceof Static_ || $returnType instanceof Self_) {
                $types[] = $class;
            }
        }

        return $this->completeParentClasses($types);
    }

    /**
     * @param string[] $types
     * @return string[]
     */
    private function resolveFirstMatchingTypeAndMethod(array $types, string $method): array
    {
        foreach ($types as $type) {
            $returnTypes = $this->getMethodReturnTypes($type, $method);
            if (count($returnTypes) > 0) {
                return $this->completeParentClasses($returnTypes);
            }
        }

        return [];
    }

    /**
     * @param string[] $types
     * @return string[]
     */
    private function completeParentClasses(array $types): array
    {
        foreach ($types as $type) {
            $types = array_merge($types, $this->smartClassReflector->getClassParents($type));
        }

        return $types;
    }
}
