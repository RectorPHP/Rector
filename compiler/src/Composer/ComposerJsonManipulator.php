<?php

declare(strict_types=1);

namespace Rector\Compiler\Composer;

use Nette\Utils\FileSystem as NetteFileSystem;
use Nette\Utils\Json;
use Rector\Compiler\Differ\ConsoleDiffer;
use Symfony\Component\Filesystem\Filesystem;

final class ComposerJsonManipulator
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $originalComposerJsonFileContent;

    /**
     * @var ConsoleDiffer
     */
    private $consoleDiffer;

    public function __construct(Filesystem $filesystem, ConsoleDiffer $consoleDiffer)
    {
        $this->filesystem = $filesystem;
        $this->consoleDiffer = $consoleDiffer;
    }

    public function fixComposerJson(string $composerJsonFile): void
    {
        $fileContent = NetteFileSystem::read($composerJsonFile);
        $this->originalComposerJsonFileContent = $fileContent;

        $json = Json::decode($fileContent, Json::FORCE_ARRAY);

        $json = $this->removeDevKeys($json);

        $json = $this->replacePHPStanWithPHPStanSrc($json);
        $json = $this->addReplace($json);

        // see https://github.com/phpstan/phpstan-src/blob/769669d4ec2a4839cb1aa25a3a29f05aa86b83ed/composer.json#L19
        $json = $this->addAllowDevPackages($json);

        $encodedJson = Json::encode($json, Json::PRETTY);

        // show diff
        $this->consoleDiffer->diff($this->originalComposerJsonFileContent, $encodedJson);

        $this->filesystem->dumpFile($composerJsonFile, $encodedJson);
    }

    /**
     * This prevent root composer.json constant override
     */
    public function restoreComposerJson(string $composerJsonFile): void
    {
        $this->filesystem->dumpFile($composerJsonFile, $this->originalComposerJsonFileContent);

        // re-run @todo composer update on root
    }

    private function removeDevKeys(array $json): array
    {
        $keysToRemove = ['replace'];

        foreach ($keysToRemove as $keyToRemove) {
            unset($json[$keyToRemove]);
        }

        return $json;
    }

    /**
     * Use phpstan/phpstan-src, because the phpstan.phar cannot be packed into rector.phar
     */
    private function replacePHPStanWithPHPStanSrc(array $json): array
    {
        // already replaced
        if (! isset($json['require']['phpstan/phpstan'])) {
            return $json;
        }

        $phpstanVersion = $json['require']['phpstan/phpstan'];
        $json['require']['phpstan/phpstan-src'] = $phpstanVersion;
        unset($json['require']['phpstan/phpstan']);

        $json['repositories'][] = [
            'type' => 'vcs',
            'url' => 'https://github.com/phpstan/phpstan-src.git',
        ];

        return $json;
    }

    /**
     * This prevent installing packages, that are not needed here.
     */
    private function addReplace(array $json): array
    {
        $json['replace'] = [
            'symfony/var-dumper' => '*',
        ];

        return $json;
    }

    private function addAllowDevPackages(array $json): array
    {
        $json['minimum-stability'] = 'dev';
        $json['prefer-stable'] = true;

        return $json;
    }
}
