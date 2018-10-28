<?php declare(strict_types=1);

namespace Rector\CodeQuality\Tests\Rector\If_\SimplifyIfNotNullReturnRector;

use Iterator;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

/**
 * @covers \Rector\CodeQuality\Rector\If_\SimplifyIfNotNullReturnRector
 */
final class SimplifyIfNotNullReturnRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideWrongToFixedFiles()
     */
    public function test(string $wrong, string $fixed): void
    {
        $this->doTestFileMatchesExpectedContent($wrong, $fixed);
    }

    public function provideWrongToFixedFiles(): Iterator
    {
        yield [__DIR__ . '/Wrong/wrong.php.inc', __DIR__ . '/Correct/correct.php.inc'];
        yield [__DIR__ . '/Wrong/wrong2.php.inc', __DIR__ . '/Correct/correct2.php.inc'];
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/config.yml';
    }
}
