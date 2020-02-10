<?php

declare(strict_types=1);

namespace Rector\Core\Testing;

use Iterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class StaticFixtureProvider
{
    public static function yieldFilesFromDirectory(string $directory, string $suffix): Iterator
    {
        $fileInfos = self::findFilesInDirectory($directory, $suffix);

        foreach ($fileInfos as $fileInfo) {
            yield [$fileInfo->getPathName()];
        }
    }

    /**
     * @return SplFileInfo[]
     */
    private static function findFilesInDirectory(string $directory, string $suffix): array
    {
        $finder = Finder::create()->in($directory)->files()
            ->name($suffix);

        $fileInfos = iterator_to_array($finder);

        return array_values($fileInfos);
    }
}
