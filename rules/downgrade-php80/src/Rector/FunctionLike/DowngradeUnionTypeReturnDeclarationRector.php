<?php

declare(strict_types=1);

namespace Rector\DowngradePhp80\Rector\FunctionLike;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\UnionType;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\DowngradePhp72\Rector\FunctionLike\AbstractDowngradeReturnDeclarationRector;

/**
 * @see \Rector\DowngradePhp80\Tests\Rector\FunctionLike\DowngradeUnionTypeReturnDeclarationRector\DowngradeUnionTypeReturnDeclarationRectorTest
 */
final class DowngradeUnionTypeReturnDeclarationRector extends AbstractDowngradeReturnDeclarationRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'Remove returning union types, add a @return tag instead',
            [
                new CodeSample(
                    <<<'PHP'
<?php

class SomeClass
{
    public function getSomeObject(bool $flag): string|int
    {
        if ($flag) {
            return 1;
        }
        return 'Hello world';
    }
}
PHP
                    ,
                    <<<'PHP'
<?php

class SomeClass
{
    /**
     * @return string|int
     */
    public function getSomeObject(bool $flag)
    {
        if ($flag) {
            return 1;
        }
        return 'Hello world';
    }
}
PHP
                ),
            ]
        );
    }

    public function getPhpVersionFeature(): string
    {
        return PhpVersionFeature::UNION_TYPES;
    }

    /**
     * @param ClassMethod|Function_ $functionLike
     */
    public function shouldRemoveReturnDeclaration(FunctionLike $functionLike): bool
    {
        if ($functionLike->returnType === null) {
            return false;
        }

        // Check it is the union type
        return $functionLike->returnType instanceof UnionType;
    }
}
