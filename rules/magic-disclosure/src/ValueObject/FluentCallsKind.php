<?php

declare(strict_types=1);

namespace Rector\MagicDisclosure\ValueObject;

final class FluentCallsKind
{
    /**
     * @var string
     */
    public const NORMAL = 'normal';

    /**
     * @var string
     */
    public const IN_ARGS = 'in_args';
}
