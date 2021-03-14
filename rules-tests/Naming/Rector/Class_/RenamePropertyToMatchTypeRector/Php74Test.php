<?php

declare(strict_types=1);

namespace Rector\Tests\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;

use Iterator;
use Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class Php74Test extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $fileInfo): void
    {
        $this->doTestFileInfo($fileInfo);
    }

    /**
     * @return Iterator<SmartFileInfo>
     */
    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/FixturePhp74');
    }

    protected function getRectorClass(): string
    {
        return RenamePropertyToMatchTypeRector::class;
    }
}
