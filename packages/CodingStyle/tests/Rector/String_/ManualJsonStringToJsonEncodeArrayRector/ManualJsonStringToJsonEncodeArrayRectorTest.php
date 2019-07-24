<?php declare(strict_types=1);

namespace Rector\CodingStyle\Tests\Rector\String_\ManualJsonStringToJsonEncodeArrayRector;

use Rector\CodingStyle\Rector\String_\ManualJsonStringToJsonEncodeArrayRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ManualJsonStringToJsonEncodeArrayRectorTest extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->doTestFiles([
            __DIR__ . '/Fixture/fixture.php.inc',
            __DIR__ . '/Fixture/concat_json.php.inc',
            __DIR__ . '/Fixture/multiline_concat_json.php.inc',
            __DIR__ . '/Fixture/tripleline_multiline_concat_json.php.inc',
        ]);
    }

    protected function getRectorClass(): string
    {
        return ManualJsonStringToJsonEncodeArrayRector::class;
    }
}
