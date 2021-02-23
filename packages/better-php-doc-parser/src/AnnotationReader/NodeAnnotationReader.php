<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\AnnotationReader;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\Reader;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\DoctrineAnnotationGenerated\PhpDocNode\ConstantReferenceIdentifierRestorer;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use ReflectionClass;
use ReflectionProperty;
use Symplify\PackageBuilder\Reflection\PrivatesAccessor;
use Throwable;

final class NodeAnnotationReader
{
    /**
     * @var string[]
     */
    private $alreadyProvidedAnnotations = [];

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    /**
     * @var ConstantReferenceIdentifierRestorer
     */
    private $constantReferenceIdentifierRestorer;

    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(
        ConstantReferenceIdentifierRestorer $constantReferenceIdentifierRestorer,
        NodeNameResolver $nodeNameResolver,
        Reader $reader,
        ReflectionProvider $reflectionProvider
    ) {
        $this->reader = $reader;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->constantReferenceIdentifierRestorer = $constantReferenceIdentifierRestorer;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function readAnnotation(Node $node, string $annotationClass): ?object
    {
        if ($node instanceof Property) {
            return $this->readPropertyAnnotation($node, $annotationClass);
        }

        if ($node instanceof ClassMethod) {
            return $this->readMethodAnnotation($node, $annotationClass);
        }

        if ($node instanceof Class_) {
            return $this->readClassAnnotation($node, $annotationClass);
        }

        return null;
    }

    public function readClassAnnotation(Class_ $class, string $annotationClassName): ?object
    {
        $classReflection = $this->createClassReflectionFromNode($class);
        $nativeClassReflection = $classReflection->getNativeReflection();

        try {
            // covers cases like https://github.com/rectorphp/rector/issues/3046

            /** @var object[] $classAnnotations */
            $classAnnotations = $this->reader->getClassAnnotations($nativeClassReflection);
            return $this->matchNextAnnotation($classAnnotations, $annotationClassName, $class);
        } catch (AnnotationException $annotationException) {
            // unable to load
            return null;
        }
    }

    public function readPropertyAnnotation(Property $property, string $annotationClassName): ?object
    {
        $propertyReflection = $this->getNativePropertyReflection($property);

        try {
            // covers cases like https://github.com/rectorphp/rector/issues/3046

            // @todo this will require original reflection
            /** @var object[] $propertyAnnotations */
            $propertyAnnotations = $this->reader->getPropertyAnnotations($propertyReflection);
            return $this->matchNextAnnotation($propertyAnnotations, $annotationClassName, $property);
        } catch (AnnotationException $annotationException) {
            // unable to load
            return null;
        }
    }

    private function readMethodAnnotation(ClassMethod $classMethod, string $annotationClassName): ?object
    {
        /** @var string $className */
        $className = $classMethod->getAttribute(AttributeKey::CLASS_NAME);

        /** @var string $methodName */
        $methodName = $this->nodeNameResolver->getName($classMethod);

        $reflectionClass = $this->reflectionProvider->getClass($className);
        $methodReflection = $reflectionClass->getNativeMethod($methodName);

        // @see https://github.com/phpstan/phpstan-src/commit/5fad625b7770b9c5beebb19ccc1a493839308fb4
        $privatesAccessor = new PrivatesAccessor();
        $nativeMethodReflection = $privatesAccessor->getPrivateProperty($methodReflection, 'reflection');

        try {
            // covers cases like https://github.com/rectorphp/rector/issues/3046

            /** @var object[] $methodAnnotations */
            $methodAnnotations = $this->reader->getMethodAnnotations($nativeMethodReflection);
            foreach ($methodAnnotations as $methodAnnotation) {
                if (! is_a($methodAnnotation, $annotationClassName, true)) {
                    continue;
                }

                $objectHash = md5(spl_object_hash($classMethod) . serialize($methodAnnotation));
                if (in_array($objectHash, $this->alreadyProvidedAnnotations, true)) {
                    continue;
                }

                $this->alreadyProvidedAnnotations[] = $objectHash;
                $this->constantReferenceIdentifierRestorer->restoreObject($methodAnnotation);

                return $methodAnnotation;
            }
        } catch (AnnotationException $annotationException) {
            // unable to load
            return null;
        }

        return null;
    }

    private function createClassReflectionFromNode(Class_ $class): ClassReflection
    {
        /** @var string $className */
        $className = $this->nodeNameResolver->getName($class);

        // covers cases like https://github.com/rectorphp/rector/issues/3230#issuecomment-683317288

        return $this->reflectionProvider->getClass($className);
//        return new ReflectionClass($className);
    }

    /**
     * @param object[] $annotations
     */
    private function matchNextAnnotation(array $annotations, string $annotationClassName, Node $node): ?object
    {
        foreach ($annotations as $annotatoin) {
            if (! is_a($annotatoin, $annotationClassName, true)) {
                continue;
            }

            $objectHash = md5(spl_object_hash($node) . serialize($annotatoin));
            if (in_array($objectHash, $this->alreadyProvidedAnnotations, true)) {
                continue;
            }

            $this->alreadyProvidedAnnotations[] = $objectHash;
            $this->constantReferenceIdentifierRestorer->restoreObject($annotatoin);

            return $annotatoin;
        }

        return null;
    }

    private function getNativePropertyReflection(Property $property): ?ReflectionProperty
    {
        /** @var string $propertyName */
        $propertyName = $this->nodeNameResolver->getName($property);

        /** @var string|null $className */
        $className = $property->getAttribute(AttributeKey::CLASS_NAME);
        if ($className === null) {
            // probably fresh node
            return null;
        }

        if (! $this->reflectionProvider->hasClass($className)) {
            // probably fresh node
            return null;
        }

        try {
            $classReflection = $this->reflectionProvider->getClass($className);
            $propertyScope = $property->getAttribute(AttributeKey::SCOPE);
            $propertyReflection = $classReflection->getProperty($propertyName, $propertyScope);

            // @see https://github.com/phpstan/phpstan-src/commit/5fad625b7770b9c5beebb19ccc1a493839308fb4
            $privatesAccessor = new PrivatesAccessor();
            return $privatesAccessor->getPrivateProperty($propertyReflection, 'reflection');

            // return new ReflectionProperty($className, $propertyName);
        } catch (Throwable $throwable) {
            // in case of PHPUnit property or just-added property
            return null;
        }
    }
}
