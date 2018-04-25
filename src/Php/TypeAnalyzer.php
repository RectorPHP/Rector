<?php declare(strict_types=1);

namespace Rector\Php;

use Nette\Utils\Strings;

final class TypeAnalyzer
{
    public function isPhpReservedType(string $type): bool
    {
        return in_array($type, ['string', 'bool', 'mixed', 'object', 'iterable', 'array'], true);
    }

    public function isNullableType(string $type): bool
    {
        return Strings::startsWith($type, '?');
    }
}
