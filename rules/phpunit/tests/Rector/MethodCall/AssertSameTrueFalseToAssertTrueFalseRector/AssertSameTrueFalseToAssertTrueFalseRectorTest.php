<?php

declare(strict_types=1);

namespace Rector\PHPUnit\Tests\Rector\MethodCall\AssertSameTrueFalseToAssertTrueFalseRector;

use Iterator;
use Rector\PHPUnit\Rector\MethodCall\AssertSameTrueFalseToAssertTrueFalseRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class AssertSameTrueFalseToAssertTrueFalseRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $fileInfo): void
    {
        $this->doTestFileInfo($fileInfo);
    }

    /**
     * @return Iterator<mixed, SmartFileInfo[]>
     */
    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    protected function getRectorClass(): string
    {
        return AssertSameTrueFalseToAssertTrueFalseRector::class;
    }
}
