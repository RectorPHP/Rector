<?php

declare(strict_types=1);

namespace Rector\Restoration\ClassMap;

use Nette\Loaders\RobotLoader;
use Symplify\ComposerJsonManipulator\ComposerJsonFactory;

final class ExistingClassesProvider
{
    /**
     * @var string[]
     */
    private $existingClasses = [];

    /**
     * @var ComposerJsonFactory
     */
    private $composerJsonFactory;

    public function __construct(ComposerJsonFactory $composerJsonFactory)
    {
        $this->composerJsonFactory = $composerJsonFactory;
    }

    /**
     * @return string[]
     */
    public function provide(): array
    {
        if ($this->existingClasses === []) {
            $psr4Paths = $this->getPsr4PathFromComposerJson();

            $existingClasses = $this->findClassesInDirectories($psr4Paths);

            /** @var string[] $existingClasses */
            $existingClasses = array_merge($existingClasses, get_declared_classes());

            $this->existingClasses = $existingClasses;
        }

        return $this->existingClasses;
    }

    /**
     * @return string[]
     */
    private function getPsr4PathFromComposerJson(): array
    {
        $composerJsonFilePath = getcwd() . '/composer.json';
        $composerJson = $this->composerJsonFactory->createFromFilePath($composerJsonFilePath);

        $directories = [];

        foreach ($composerJson->getAutoload()['psr-4'] ?? [] as $paths) {
            if (is_array($paths)) {
                $directories = array_merge($directories, $paths);
            } else {
                $directories[] = $paths;
            }
        }

        $classmapPaths = $composerJson->getAutoload()['classmap'] ?? [];

        return array_merge($directories, $classmapPaths);
    }

    /**
     * @param string[] $directories
     * @return string[]
     */
    private function findClassesInDirectories(array $directories): array
    {
        $robotLoader = new RobotLoader();
        $robotLoader->setTempDirectory(sys_get_temp_dir() . '/rector_restore');

        foreach ($directories as $path) {
            $robotLoader->addDirectory(getcwd() . '/' . $path);
        }

        $classNames = [];
        foreach (array_keys($robotLoader->getIndexedClasses()) as $className) {
            if (! is_string($className)) {
                continue;
            }

            $classNames[] = $className;
        }

        return $classNames;
    }
}
