<?php

declare(strict_types=1);

namespace Rector\Tests\Rector\Property\RenamePropertyRector;

use Iterator;
use Rector\Rector\Property\RenamePropertyRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Rector\Tests\Rector\Property\RenamePropertyRector\Source\ClassWithProperties;

final class RenamePropertyRectorTest extends AbstractRectorTestCase
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
            RenamePropertyRector::class => [
                '$oldToNewPropertyByTypes' => [
                    ClassWithProperties::class => [
                        'oldProperty' => 'newProperty',
                        'anotherOldProperty' => 'anotherNewProperty',
                    ],
                ],
            ],
        ];
    }
}
