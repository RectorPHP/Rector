<?php

declare(strict_types=1);

namespace Rector\Tests\BetterPhpDocParser\PhpDocParser\StaticDoctrineAnnotationParser;

use Iterator;
use Rector\BetterPhpDocParser\PhpDocInfo\TokenIteratorFactory;
use Rector\BetterPhpDocParser\PhpDocParser\StaticDoctrineAnnotationParser;
use Rector\BetterPhpDocParser\ValueObject\PhpDoc\DoctrineAnnotation\CurlyListNode;
use Rector\Testing\PHPUnit\AbstractTestCase;

final class StaticDoctrineAnnotationParserTest extends AbstractTestCase
{
    /**
     * @var StaticDoctrineAnnotationParser
     */
    private $staticDoctrineAnnotationParser;

    /**
     * @var TokenIteratorFactory
     */
    private $tokenIteratorFactory;

    protected function setUp(): void
    {
        $this->boot();

        $this->tokenIteratorFactory = $this->getService(TokenIteratorFactory::class);
        $this->staticDoctrineAnnotationParser = $this->getService(StaticDoctrineAnnotationParser::class);
    }

    /**
     * @dataProvider provideData()
     * @param mixed $expectedValue
     */
    public function test(string $docContent, $expectedValue): void
    {
        $betterTokenIterator = $this->tokenIteratorFactory->create($docContent);

        $value = $this->staticDoctrineAnnotationParser->resolveAnnotationValue($betterTokenIterator);

        // "equals" on purpose to compare 2 object with same content
        $this->assertEquals($expectedValue, $value);
    }

    public function provideData(): Iterator
    {
        $curlyListNode = new CurlyListNode(['"chalet"', '"apartment"']);
        yield ['{"chalet", "apartment"}', $curlyListNode];

        yield [
            'key={"chalet", "apartment"}', [
                'key' => $curlyListNode,
            ],
        ];
    }
}
