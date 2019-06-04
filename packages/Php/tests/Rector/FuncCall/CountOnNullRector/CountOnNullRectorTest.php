<?php declare(strict_types=1);

namespace Rector\Php\Tests\Rector\FuncCall\CountOnNullRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

/**
 * @covers \Rector\Php\Rector\FuncCall\CountOnNullRector
 */
final class CountOnNullRectorTest extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->doTestFiles([
            __DIR__ . '/Fixture/array_countable_class.php.inc',
            __DIR__ . '/Fixture/countable_annotated_params.php.inc',
            __DIR__ . '/Fixture/false_true_class.php.inc',
            __DIR__ . '/Fixture/on_null.php.inc',
            __DIR__ . '/Fixture/external_property.php.inc',
            __DIR__ . '/Fixture/preg_match_array.php.inc',
            __DIR__ . '/Fixture/local_property.php.inc',
            __DIR__ . '/Fixture/local_variable.php.inc',
            __DIR__ . '/Fixture/double_same_variable.php.inc',
        ]);
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/rector_with_php71.yaml';
    }
}
