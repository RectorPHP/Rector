<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Tests\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;

use Iterator;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class CallableThisArrayToAnonymousFunctionRectorTest extends AbstractRectorTestCase
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
        return CallableThisArrayToAnonymousFunctionRector::class;
    }
}
