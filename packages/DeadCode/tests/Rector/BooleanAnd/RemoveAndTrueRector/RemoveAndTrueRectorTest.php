<?php

declare(strict_types=1);

namespace Rector\DeadCode\Tests\Rector\BooleanAnd\RemoveAndTrueRector;

use Iterator;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RemoveAndTrueRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    protected function getRectorClass(): string
    {
        return RemoveAndTrueRector::class;
    }
}
