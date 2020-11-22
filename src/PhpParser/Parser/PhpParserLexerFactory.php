<?php

declare(strict_types=1);

namespace Rector\Core\PhpParser\Parser;

use PhpParser\Lexer;
use PhpParser\Lexer\Emulative;

/**
 * This Lexer allows Format-perserving AST Transformations.
 * @seehttpshttps://github.com/nikic/PHP-Parser/issues/344#issuecomment-298162516
 */
final class PhpParserLexerFactory
{
    public function create(): Lexer
    {
        return new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'],
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
