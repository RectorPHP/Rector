<?php

declare(strict_types=1);

namespace Rector\Generic\Tests\Rector\ClassMethod\AddMethodParentCallRector;

use Iterator;
use Rector\Core\Testing\PHPUnit\AbstractRectorTestCase;
use Rector\Generic\Rector\ClassMethod\AddMethodParentCallRector;
use Rector\Generic\Tests\Rector\ClassMethod\AddMethodParentCallRector\Source\ParentClassWithNewConstructor;
use Symplify\SmartFileSystem\SmartFileInfo;

final class AddMethodParentCallRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $fileInfo): void
    {
        $this->doTestFileInfo($fileInfo);
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
            AddMethodParentCallRector::class => [
                AddMethodParentCallRector::METHODS_BY_PARENT_TYPES => [
                    ParentClassWithNewConstructor::class => '__construct',
                ],
            ],
        ];
    }
}
