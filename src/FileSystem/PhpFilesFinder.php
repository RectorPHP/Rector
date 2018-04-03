<?php declare(strict_types=1);

namespace Rector\FileSystem;

use Rector\Exception\FileSystem\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class PhpFilesFinder
{
    /**
     * @param string[] $source
     * @return SplFileInfo[]
     */
    public function findInDirectoriesAndFiles(array $source): array
    {
        $files = [];
        $directories = [];

        foreach ($source as $singleSource) {
            if (is_file($singleSource)) {
                $files[] = new SplFileInfo($singleSource, '', '');
            } else {
                $directories[] = $singleSource;
            }
        }

        if (count($directories) > 0) {
            $files = array_merge($files, $this->findInDirectories($directories));
        }

        return $files;
    }

    /**
     * @param string[] $directories
     * @return SplFileInfo[]
     */
    private function findInDirectories(array $directories): array
    {
        $this->ensureDirectoriesExist($directories);

        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->in($directories)
            ->exclude([
                'examples',
                'Examples',
                'stubs',
                'Stubs',
                'fixtures',
                'Fixtures',
                'polyfill',
                'Polyfill',
            ])
            ->notName('*polyfill*')
            ->sortByName();

        return iterator_to_array($finder->getIterator());
    }

    /**
     * @param string[] $directories
     */
    private function ensureDirectoriesExist(array $directories): void
    {
        foreach ($directories as $directory) {
            if (file_exists($directory)) {
                continue;
            }

            throw new DirectoryNotFoundException(sprintf('Directory "%s" was not found.', $directory));
        }
    }
}
