<?php

declare(strict_types=1);

namespace Rector\Core\Reflection;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Rector\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
use ReflectionMethod;

final class ClassMethodReflectionFactory
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function createFromPHPStanTypeAndMethodName(Type $type, string $methodName): ?ReflectionMethod
    {
        if ($type instanceof ShortenedObjectType) {
            return $this->createReflectionMethodIfExists($type->getFullyQualifiedName(), $methodName);
        }

        if ($type instanceof ObjectType) {
            return $this->createReflectionMethodIfExists($type->getClassName(), $methodName);
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            foreach ($type->getTypes() as $unionedType) {
                if (! $unionedType instanceof ObjectType) {
                    continue;
                }

                $methodReflection = $this->createFromPHPStanTypeAndMethodName($unionedType, $methodName);
                if (! $methodReflection instanceof ReflectionMethod) {
                    continue;
                }

                return $methodReflection;
            }
        }

        return null;
    }

    public function createReflectionMethodIfExists(string $class, string $method): ?ReflectionMethod
    {
        if (! $this->reflectionProvider->hasClass($class)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($class);
        $nativeClassReflection = $classReflection->getNativeReflection();
        if (! $nativeClassReflection->hasMethod($method)) {
            return null;
        }

        return $nativeClassReflection->getMethod($method);
    }
}
