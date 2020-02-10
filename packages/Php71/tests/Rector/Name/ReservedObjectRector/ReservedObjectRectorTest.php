<?php

declare(strict_types=1);

namespace Rector\Php71\Tests\Rector\Name\ReservedObjectRector;

use Iterator;
use Rector\Php71\Rector\Name\ReservedObjectRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ReservedObjectRectorTest extends AbstractRectorTestCase
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
            ReservedObjectRector::class => [
                '$reservedKeywordsToReplacements' => [
                    'ReservedObject' => 'SmartObject',
                    'Object' => 'AnotherSmartObject',
                ],
            ],
        ];
    }
}
