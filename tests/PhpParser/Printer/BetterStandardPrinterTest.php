<?php

declare(strict_types=1);

namespace Rector\Core\Tests\PhpParser\Printer;

use Iterator;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Scalar\String_;
use Rector\Core\HttpKernel\RectorKernel;
use Rector\Core\PhpParser\Printer\BetterStandardPrinter;
use Symplify\PackageBuilder\Tests\AbstractKernelTestCase;

final class BetterStandardPrinterTest extends AbstractKernelTestCase
{
    /**
     * @var BetterStandardPrinter
     */
    private $betterStandardPrinter;

    protected function setUp(): void
    {
        $this->bootKernel(RectorKernel::class);
        $this->betterStandardPrinter = self::$container->get(BetterStandardPrinter::class);
    }

    /**
     * @dataProvider provideDataForDoubleSlashEscaping()
     */
    public function testDoubleSlashEscaping(string $content, string $expectedOutput): void
    {
        $printed = $this->betterStandardPrinter->print(new String_($content));
        $this->assertSame($expectedOutput, $printed);
    }

    public function provideDataForDoubleSlashEscaping(): Iterator
    {
        yield ['Vendor\Name', "'Vendor\Name'"];
        yield ['Vendor\\', "'Vendor\\\\'"];
        yield ["Vendor'Name", "'Vendor\'Name'"];
    }

    public function testYield(): void
    {
        $printed = $this->betterStandardPrinter->print(new Yield_(new String_('value')));
        $this->assertSame("yield 'value'", $printed);

        $printed = $this->betterStandardPrinter->print(new Yield_());
        $this->assertSame('yield', $printed);
    }
}
