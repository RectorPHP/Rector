<?php

declare(strict_types=1);

namespace Rector\Tests\Rector\New_\NewToStaticCallRector;

use Iterator;
use Rector\Rector\New_\NewToStaticCallRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Rector\Tests\Rector\New_\NewToStaticCallRector\Source\FromNewClass;
use Rector\Tests\Rector\New_\NewToStaticCallRector\Source\IntoStaticClass;

final class NewToStaticCallRectorTest extends AbstractRectorTestCase
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

    /**
     * @return mixed[]
     */
    protected function getRectorsWithConfiguration(): array
    {
        return [
            NewToStaticCallRector::class => [
                '$typeToStaticCalls' => [
                    FromNewClass::class => [IntoStaticClass::class, 'run'],
                ],
            ],
        ];
    }
}
