<?php

declare(strict_types=1);

namespace Rector\Autodiscovery\Tests\Rector\FileSystem\MoveServicesBySuffixToDirectoryRector;

use Iterator;
use Rector\Autodiscovery\Rector\FileSystem\MoveServicesBySuffixToDirectoryRector;
use Rector\Core\Testing\PHPUnit\AbstractFileSystemRectorTestCase;

final class MoveServicesBySuffixToDirectoryRectorTest extends AbstractFileSystemRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(string $originalFile, string $expectedFileLocation, string $expectedFileContent): void
    {
        $this->doTestFile($originalFile);

        $this->assertFileExists($expectedFileLocation);
        $this->assertFileEquals($expectedFileContent, $expectedFileLocation);
    }

    public function provideData(): Iterator
    {
        yield [
            __DIR__ . '/Source/Entity/AppleRepository.php',
            $this->getFixtureTempDirectory() . '/Source/Repository/AppleRepository.php',
            __DIR__ . '/Expected/Repository/ExpectedAppleRepository.php',
        ];

        yield 'prefix_same_namespace' => [
            __DIR__ . '/Source/Controller/BananaCommand.php',
            $this->getFixtureTempDirectory() . '/Source/Command/BananaCommand.php',
            __DIR__ . '/Expected/Command/ExpectedBananaCommand.php',
        ];

        yield [
            __DIR__ . '/Source/Mapper/CorrectMapper.php',
            $this->getFixtureTempDirectory() . '/Source/Mapper/CorrectMapper.php',
            // same content, no change
            __DIR__ . '/Source/Mapper/CorrectMapper.php',
        ];
    }

    protected function getRectorsWithConfiguration(): array
    {
        return [
            MoveServicesBySuffixToDirectoryRector::class => [
                '$groupNamesBySuffix' => ['Repository', 'Command', 'Mapper'],
            ],
        ];
    }
}
