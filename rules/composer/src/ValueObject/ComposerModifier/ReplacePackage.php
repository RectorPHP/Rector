<?php

declare(strict_types=1);

namespace Rector\Composer\ValueObject\ComposerModifier;

use Rector\Composer\Contract\ComposerModifier\ComposerModifierConfigurationInterface;
use Rector\Composer\ValueObject\Version\Version;
use Webmozart\Assert\Assert;

/**
 * Replace one package for another
 * @see \Rector\Composer\Tests\Modifier\ReplacePackageTest
 */
final class ReplacePackage implements ComposerModifierConfigurationInterface
{
    /** @var string */
    private $oldPackageName;

    /** @var string */
    private $newPackageName;

    /** @var Version */
    private $targetVersion;

    /**
     * @param string $oldPackageName name of package to be replaced (vendor1/package1)
     * @param string $newPackageName new name of package (vendor2/package2)
     * @param string $targetVersion target package version (1.2.3, ^1.2, ~1.2.3 etc.)
     */
    public function __construct(string $oldPackageName, string $newPackageName, string $targetVersion)
    {
        Assert::notSame($oldPackageName, $newPackageName, '$oldPackageName cannot be the same as $newPackageName. If you want to change version of package, use ' . ChangePackageVersion::class);

        $this->oldPackageName = $oldPackageName;
        $this->newPackageName = $newPackageName;
        $this->targetVersion = new Version($targetVersion);
    }

    public function getOldPackageName(): string
    {
        return $this->oldPackageName;
    }

    public function getNewPackageName(): string
    {
        return $this->newPackageName;
    }

    public function getTargetVersion(): Version
    {
        return $this->targetVersion;
    }
}
