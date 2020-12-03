<?php

declare(strict_types=1);

namespace Rector\DowngradePhp80\Tests\Rector\Class_\DowngradePropertyPromotionToConstructorPropertyAssignRector;

use Iterator;
use Rector\DowngradePhp80\Rector\Class_\DowngradePropertyPromotionToConstructorPropertyAssignRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class DowngradePropertyPromotionToConstructorPropertyAssignRectorTest extends AbstractRectorTestCase
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

    protected function getRectorClass(): string
    {
        return DowngradePropertyPromotionToConstructorPropertyAssignRector::class;
    }
}
