<?php

declare(strict_types=1);

namespace Rector\DowngradePhp80\Tests\Rector\Expr\DowngradeNullsafeToTernaryOperatorRector;

use Iterator;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\DowngradePhp80\Rector\Expr\DowngradeNullsafeToTernaryOperatorRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class DowngradeNullsafeToTernaryOperatorRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     * @requires PHP >= 8.0
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
        return DowngradeNullsafeToTernaryOperatorRector::class;
    }

    protected function getPhpVersion(): int
    {
        return PhpVersionFeature::NULLSAFE_OPERATOR - 1;
    }
}
