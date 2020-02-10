<?php

declare(strict_types=1);

namespace Rector\CakePHPToSymfony\Tests\Rector\ClassMethod\CakePHPControllerRenderToSymfonyRector;

use Iterator;
use Rector\CakePHPToSymfony\Rector\ClassMethod\CakePHPControllerRenderToSymfonyRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class CakePHPControllerRenderToSymfonyRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideDataForTest()
     */
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public function provideDataForTest(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    protected function getRectorClass(): string
    {
        return CakePHPControllerRenderToSymfonyRector::class;
    }
}
