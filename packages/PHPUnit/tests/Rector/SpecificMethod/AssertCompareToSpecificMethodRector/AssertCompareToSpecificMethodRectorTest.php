<?php

declare(strict_types=1);

namespace Rector\PHPUnit\Tests\Rector\SpecificMethod\AssertCompareToSpecificMethodRector;

use Iterator;
use Rector\PHPUnit\Rector\SpecificMethod\AssertCompareToSpecificMethodRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class AssertCompareToSpecificMethodRectorTest extends AbstractRectorTestCase
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
        return AssertCompareToSpecificMethodRector::class;
    }
}
