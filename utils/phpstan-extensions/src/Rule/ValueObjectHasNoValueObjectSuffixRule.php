<?php

declare(strict_types=1);

namespace Rector\PHPStanExtensions\Rule;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @see \Rector\PHPStanExtensions\Tests\Rule\ValueObjectHasNoValueObjectSuffixRule\ValueObjectHasNoValueObjectSuffixRuleTest
 */
class ValueObjectHasNoValueObjectSuffixRule implements Rule
{
    public const ERROR = 'Value Object class name "%s" is incorrect. The correct class name is "%s".';

    /**
     * @see https://regex101.com/r/3jsBnt/1
     */
    private const REGEX_PATTERN = '#ValueObject$#';

    private const VALUE_OBJECT_NAMESPACE = 'ValueObject';

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name === null) {
            return [];
        }

        if (! $this->hasValueObjectNamespace($node)) {
            return [];
        }

        if (! $this->hasValueObjectSuffix($node)) {
            return [];
        }

        return [sprintf(
            self::ERROR,
            $node->name->toString(),
            Strings::replace($node->name->toString(), self::REGEX_PATTERN, '')
        )];
    }

    private function hasValueObjectNamespace(Class_ $class): bool
    {
        if ($class->namespacedName->parts === null) {
            return false;
        }

        return in_array(self::VALUE_OBJECT_NAMESPACE, $class->namespacedName->parts, true);
    }

    private function hasValueObjectSuffix(Class_ $class): bool
    {
        if ($class->name === null) {
            return false;
        }

        return Strings::match($class->name->toString(), self::REGEX_PATTERN) !== null;
    }
}
