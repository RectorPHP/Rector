<?php

declare(strict_types=1);

namespace Rector\PHPUnit\Tests\Rector\SpecificMethod\AssertRegExpRector;

use Iterator;
use Rector\PHPUnit\Rector\SpecificMethod\AssertRegExpRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class AssertRegExpRectorTest extends AbstractRectorTestCase
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
        return AssertRegExpRector::class;
    }
}
