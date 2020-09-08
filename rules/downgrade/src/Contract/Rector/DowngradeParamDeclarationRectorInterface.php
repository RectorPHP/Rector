<?php

declare(strict_types=1);

namespace Rector\Downgrade\Contract\Rector;

use PhpParser\Node\Param;

interface DowngradeParamDeclarationRectorInterface
{
    /**
     * Indicate if the parameter must be removed
     */
    public function shouldRemoveParamDeclaration(Param $param): bool;
}
