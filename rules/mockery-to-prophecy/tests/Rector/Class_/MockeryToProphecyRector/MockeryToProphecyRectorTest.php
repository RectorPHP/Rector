<?php

declare(strict_types=1);

namespace Rector\MockeryToProphecy\Tests\Rector\Class_\MockeryToProphecyRector;

use Iterator;
use Rector\Core\Testing\PHPUnit\AbstractRectorTestCase;
use Rector\MockeryToProphecy\Rector\MethodCall\CleanUpMockeryClose;
use Rector\MockeryToProphecy\Rector\MethodCall\MockeryCreateMockToProphizeRector;
use Symplify\SmartFileSystem\SmartFileInfo;

final class MockeryToProphecyRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $file): void
    {
        $this->doTestFileInfo($file);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    /**
     * @return string[][]
     */
    protected function getRectorsWithConfiguration(): array
    {
        return [
            MockeryCreateMockToProphizeRector::class => [],
            CleanUpMockeryClose::class => []
        ];
    }
}
