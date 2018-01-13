<?php declare(strict_types=1);

namespace Rector\Tests\Rector\Dynamic\FluentReplaceRector;

use Rector\Rector\Dynamic\FluentReplaceRector;
use Rector\Testing\PHPUnit\AbstractConfigurableRectorTestCase;

final class FluentReplaceRectorTest extends AbstractConfigurableRectorTestCase
{
    /**
     * @dataProvider provideWrongToFixedFiles()
     */
    public function test(string $wrong, string $fixed): void
    {
        $this->doTestFileMatchesExpectedContent($wrong, $fixed);
    }

    /**
     * @return string[][]
     */
    public function provideWrongToFixedFiles(): array
    {
        return [
            [__DIR__ . '/Wrong/wrong.php.inc', __DIR__ . '/Correct/correct.php.inc'],
        ];
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/config/rector.yml';
    }

    /**
     * @return string[]
     */
    protected function getRectorClasses(): array
    {
        return [FluentReplaceRector::class];
    }
}
