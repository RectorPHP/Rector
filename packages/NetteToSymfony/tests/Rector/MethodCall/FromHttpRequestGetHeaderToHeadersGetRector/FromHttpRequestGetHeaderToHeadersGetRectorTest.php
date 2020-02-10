<?php

declare(strict_types=1);

namespace Rector\NetteToSymfony\Tests\Rector\MethodCall\FromHttpRequestGetHeaderToHeadersGetRector;

use Iterator;
use Rector\NetteToSymfony\Rector\MethodCall\FromHttpRequestGetHeaderToHeadersGetRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class FromHttpRequestGetHeaderToHeadersGetRectorTest extends AbstractRectorTestCase
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
        return FromHttpRequestGetHeaderToHeadersGetRector::class;
    }
}
