<?php

declare(strict_types=1);

namespace Rector\TypeDeclaration\Tests\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector\Source;

final class ReturnSelfFromSource
{
    public static function fromEvent() : self
    {
        return new self;
    }
}