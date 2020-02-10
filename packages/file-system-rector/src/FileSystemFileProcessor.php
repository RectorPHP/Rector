<?php

declare(strict_types=1);

namespace Rector\FileSystemRector;

use Rector\FileSystemRector\Contract\FileSystemRectorInterface;
use Symplify\SmartFileSystem\SmartFileInfo;

final class FileSystemFileProcessor
{
    /**
     * @var FileSystemRectorInterface[]
     */
    private $fileSystemRectors = [];

    /**
     * @param FileSystemRectorInterface[] $fileSystemRectors
     */
    public function __construct(array $fileSystemRectors = [])
    {
        $this->fileSystemRectors = $fileSystemRectors;
    }

    public function processFileInfo(SmartFileInfo $smartFileInfo): void
    {
        foreach ($this->fileSystemRectors as $fileSystemRector) {
            $fileSystemRector->refactor($smartFileInfo);
        }
    }

    public function getFileSystemRectorsCount(): int
    {
        return count($this->fileSystemRectors);
    }
}
