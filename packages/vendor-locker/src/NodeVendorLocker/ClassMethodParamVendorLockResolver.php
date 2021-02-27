<?php

declare(strict_types=1);

namespace Rector\VendorLocker\NodeVendorLocker;

use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use Rector\NodeTypeResolver\Node\AttributeKey;

final class ClassMethodParamVendorLockResolver extends AbstractNodeVendorLockResolver
{
    public function isVendorLocked(ClassMethod $classMethod, int $paramPosition): bool
    {
        $scope = $classMethod->getAttribute(AttributeKey::SCOPE);
        if (! $scope instanceof Scope) {
            return false;
        }

        $classReflection = $scope->getClassReflection();
        if (! $classReflection instanceof ClassReflection) {
            return false;
        }

        if (! $this->hasParentClassChildrenClassesOrImplementsInterface($classReflection)) {
            return false;
        }

        $methodName = $this->nodeNameResolver->getName($classMethod);

        if ($classReflection->getParentClass() !== false) {
            $vendorLock = $this->isParentClassVendorLocking(
                $classReflection->getParentClass(),
                $paramPosition,
                $methodName
            );
            if ($vendorLock !== null) {
                return $vendorLock;
            }
        }

        if ($classReflection->isClass()) {
            return $this->isMethodVendorLockedByInterface($classReflection, $methodName);
        }

        if ($classReflection->isInterface()) {
            return $this->isMethodVendorLockedByInterface($classReflection, $methodName);
        }

        return false;
    }

    private function isParentClassVendorLocking(
        ClassReflection $parentClassReflection,
        int $paramPosition,
        string $methodName
    ): ?bool {
        $parentClass = $this->nodeRepository->findClass($parentClassReflection->getName());
        if ($parentClass !== null) {
            $parentClassMethod = $parentClass->getMethod($methodName);
            // parent class method in local scope → it's ok
            if ($parentClassMethod !== null) {
                // parent method has no type → we cannot change it here
                if (! isset($parentClassMethod->params[$paramPosition])) {
                    return false;
                }
                return $parentClassMethod->params[$paramPosition]->type === null;
            }
        }

        if ($parentClassReflection->hasMethod($methodName)) {
            // parent class method in external scope → it's not ok
            // if not, look for it's parent parent
            return true;
        }

        return null;
    }
}
