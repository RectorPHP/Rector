<?php

declare(strict_types=1);

namespace Rector\PHPUnit\Tests\Rector\MethodCall\DelegateExceptionArgumentsRector;

use Iterator;
use Rector\PHPUnit\Rector\MethodCall\DelegateExceptionArgumentsRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class DelegateExceptionArgumentsRectorTest extends AbstractRectorTestCase
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
        return DelegateExceptionArgumentsRector::class;
    }
}
