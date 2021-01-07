<?php

namespace Rector\Composer\Tests\Modifier;

use Nette\Utils\Json;
use Rector\Composer\ValueObject\ComposerModifier\AddPackageToRequire;
use Rector\Composer\ValueObject\ComposerModifier\ReplacePackage;
use Rector\Composer\ValueObject\ComposerModifier\ChangePackageVersion;
use Rector\Composer\ValueObject\ComposerModifier\MovePackageToRequireDev;
use Rector\Composer\ValueObject\ComposerModifier\RemovePackage;
use Rector\Composer\Modifier\ComposerModifier;
use Rector\Core\HttpKernel\RectorKernel;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJson;
use Symplify\PackageBuilder\Testing\AbstractKernelTestCase;

final class ComposerModifierTest extends AbstractKernelTestCase
{
    protected function setUp(): void
    {
        $this->bootKernelWithConfigs(RectorKernel::class, []);
    }

    public function testRefactorWithOneAddedPackage(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $composerModifier->configure([
            new AddPackageToRequire('vendor1/package3', '^3.0'),
        ]);

        $composerJson = new ComposerJson();
        $composerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
        ]);

        $changedComposerJson = new ComposerJson();
        $changedComposerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
            'vendor1/package3' => '^3.0',
        ]);
        $this->assertEquals($changedComposerJson, $composerModifier->modify($composerJson));
    }

    public function testRefactorWithOneAddedAndOneRemovedPackage(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $composerModifier->configure([
            new AddPackageToRequire('vendor1/package3', '^3.0'),
            new RemovePackage('vendor1/package1'),
        ]);

        $composerJson = new ComposerJson();
        $composerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
        ]);

        $changedComposerJson = new ComposerJson();
        $changedComposerJson->setRequire([
            'vendor1/package2' => '^2.0',
            'vendor1/package3' => '^3.0',
        ]);
        $this->assertEquals($changedComposerJson, $composerModifier->modify($composerJson));
    }

    public function testRefactorWithAddedAndRemovedSamePackage(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $composerModifier->configure([
            new AddPackageToRequire('vendor1/package3', '^3.0'),
            new RemovePackage('vendor1/package3'),
        ]);

        $composerJson = new ComposerJson();
        $composerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
        ]);

        $changedComposerJson = new ComposerJson();
        $changedComposerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
        ]);
        $this->assertEquals($changedComposerJson, $composerModifier->modify($composerJson));
    }

    public function testRefactorWithRemovedAndAddedBackSamePackage(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $composerModifier->configure([
            new RemovePackage('vendor1/package3'),
            new AddPackageToRequire('vendor1/package3', '^3.0'),
        ]);

        $composerJson = new ComposerJson();
        $composerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
        ]);

        $changedComposerJson = new ComposerJson();
        $changedComposerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
            'vendor1/package3' => '^3.0',
        ]);
        $this->assertEquals($changedComposerJson, $composerModifier->modify($composerJson));
    }

    public function testRefactorWithMovedAndChangedPackages(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $composerModifier->configure([
            new MovePackageToRequireDev('vendor1/package1'),
            new ReplacePackage('vendor1/package2', 'vendor2/package1', '^3.0'),
            new ChangePackageVersion('vendor1/package3', '~3.0.0'),
        ]);

        $composerJson = new ComposerJson();
        $composerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
            'vendor1/package3' => '^3.0',
        ]);

        $changedComposerJson = new ComposerJson();
        $changedComposerJson->setRequire([
            'vendor1/package3' => '~3.0.0',
            'vendor2/package1' => '^3.0',
        ]);
        $changedComposerJson->setRequireDev([
            'vendor1/package1' => '^1.0',
        ]);
        $this->assertEquals($changedComposerJson, $composerModifier->modify($composerJson));
    }

    public function testRefactorWithMultipleConfiguration(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $composerModifier->configure([
            new MovePackageToRequireDev('vendor1/package1'),
        ]);
        $composerModifier->configure([
            new ReplacePackage('vendor1/package2', 'vendor2/package1', '^3.0'),
        ]);
        $composerModifier->configure([
            new ChangePackageVersion('vendor1/package3', '~3.0.0'),
        ]);

        $composerJson = new ComposerJson();
        $composerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
            'vendor1/package3' => '^3.0',
        ]);

        $changedComposerJson = new ComposerJson();
        $changedComposerJson->setRequire([
            'vendor1/package3' => '~3.0.0',
            'vendor2/package1' => '^3.0',
        ]);
        $changedComposerJson->setRequireDev([
            'vendor1/package1' => '^1.0',
        ]);
        $this->assertEquals($changedComposerJson, $composerModifier->modify($composerJson));
    }

    public function testRefactorWithConfigurationAndReconfigurationAndConfiguration(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $composerModifier->configure([
            new MovePackageToRequireDev('vendor1/package1'),
        ]);
        $composerModifier->reconfigure([
            new ReplacePackage('vendor1/package2', 'vendor2/package1', '^3.0'),
        ]);
        $composerModifier->configure([
            new ChangePackageVersion('vendor1/package3', '~3.0.0'),
        ]);

        $composerJson = new ComposerJson();
        $composerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
            'vendor1/package3' => '^3.0',
        ]);

        $changedComposerJson = new ComposerJson();
        $changedComposerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package3' => '~3.0.0',
            'vendor2/package1' => '^3.0',
        ]);
        $this->assertEquals($changedComposerJson, $composerModifier->modify($composerJson));
    }

    public function testRefactorWithMovedAndChangedPackagesWithSortPackages(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $composerModifier->configure([
            new MovePackageToRequireDev('vendor1/package1'),
            new ReplacePackage('vendor1/package2', 'vendor1/package0', '^3.0'),
            new ChangePackageVersion('vendor1/package3', '~3.0.0'),
        ]);

        $composerJson = new ComposerJson();
        $composerJson->setConfig([
            'sort-packages' => true
        ]);
        $composerJson->setRequire([
            'vendor1/package1' => '^1.0',
            'vendor1/package2' => '^2.0',
            'vendor1/package3' => '^3.0',
        ]);

        $changedComposerJson = new ComposerJson();
        $changedComposerJson->setConfig([
            'sort-packages' => true
        ]);
        $changedComposerJson->setRequire([
            'vendor1/package0' => '^3.0',
            'vendor1/package3' => '~3.0.0',
        ]);
        $changedComposerJson->setRequireDev([
            'vendor1/package1' => '^1.0',
        ]);
        $this->assertEquals($changedComposerJson, $composerModifier->modify($composerJson));
    }

    public function testFilePath(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $this->assertEquals(getcwd() . '/composer.json', $composerModifier->getFilePath());

        $composerModifier->filePath('test/composer.json');
        $this->assertEquals('test/composer.json', $composerModifier->getFilePath());
    }

    public function testCommand(): void
    {
        /** @var ComposerModifier $composerModifier */
        $composerModifier = $this->getService(ComposerModifier::class);
        $this->assertEquals('composer update', $composerModifier->getCommand());

        $composerModifier->command('composer update --prefer-stable');
        $this->assertEquals('composer update --prefer-stable', $composerModifier->getCommand());
    }
}
