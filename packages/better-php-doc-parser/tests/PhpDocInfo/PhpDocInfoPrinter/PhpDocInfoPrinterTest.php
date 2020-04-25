<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\Tests\PhpDocInfo\PhpDocInfoPrinter;

use Iterator;
use Nette\Utils\FileSystem;
use PhpParser\Node\Stmt\Nop;

final class PhpDocInfoPrinterTest extends AbstractPhpDocInfoPrinterTest
{
    /**
     * @dataProvider provideData()
     * @dataProvider provideDataCallable()
     */
    public function test(string $docFilePath): void
    {
        $this->doComparePrintedFileEquals($docFilePath, $docFilePath);
    }

    public function testRemoveSpace(): void
    {
        $this->doComparePrintedFileEquals(
            __DIR__ . '/FixtureChanged/with_space.txt',
            __DIR__ . '/FixtureChangedExpected/with_space_expected.txt'
        );
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/FixtureBasic', '*.txt');
    }

    public function provideDataCallable(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/FixtureCallable', '*.txt');
    }

    /**
     * @dataProvider provideDataEmpty()
     */
    public function testEmpty(string $docFilePath): void
    {
        $docComment = FileSystem::read($docFilePath);
        $phpDocInfo = $this->createPhpDocInfoFromDocCommentAndNode($docComment, new Nop());

        $this->assertEmpty($this->phpDocInfoPrinter->printFormatPreserving($phpDocInfo));
    }

    public function provideDataEmpty(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/FixtureEmpty', '*.txt');
    }

    private function doComparePrintedFileEquals(string $inputDocFile, string $expectedOutputDocFile): void
    {
        $docComment = FileSystem::read($inputDocFile);
        $phpDocInfo = $this->createPhpDocInfoFromDocCommentAndNode($docComment, new Nop());

        $printedDocComment = $this->phpDocInfoPrinter->printFormatPreserving($phpDocInfo);

        $expectedDocComment = FileSystem::read($expectedOutputDocFile);
        $this->assertSame($expectedDocComment, $printedDocComment);
    }
}
