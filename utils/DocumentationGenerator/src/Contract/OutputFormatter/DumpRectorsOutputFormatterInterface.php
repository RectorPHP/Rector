<?php

declare(strict_types=1);

namespace Rector\Utils\DocumentationGenerator\Contract\OutputFormatter;

use Rector\Core\Contract\Rector\RectorInterface;

interface DumpRectorsOutputFormatterInterface
{
    public function getName(): string;

    /**
     * @param RectorInterface[] $genericRectors
     * @param RectorInterface[] $packageRectors
     */
    public function format(array $genericRectors, array $packageRectors): void;
}
