<?php

declare(strict_types=1);

namespace Rector\Shopware\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;

/**
 * @see \Rector\Shopware\Tests\Rector\MethodCall\ReplaceEnlightResponseWithSymfonyResponseRector\ReplaceEnlightResponseWithSymfonyResponseRectorTest
 */
final class ReplaceEnlightResponseWithSymfonyResponseRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Replace Enlight Response methods with Symfony Response methods', [
            new CodeSample(
                <<<'PHP'
class FrontendController extends \Enlight_Controller_Action
{
    public function run()
    {
        $this->Response()->setHeader('Foo', 'Yea');
    }
}
PHP
                ,
                <<<'PHP'
class FrontendController extends \Enlight_Controller_Action
{
    public function run()
    {
        $this->Response()->headers->set('Foo', 'Yea');
    }
}
PHP
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isObjectType($node, 'Enlight_Controller_Response_Response')) {
            return null;
        }

        $name = $this->getName($node);
        switch ($name) {
            case 'setHeader':
                return $this->modifySetHeader($node);
            case 'clearHeader':
                return $this->modifyHeader($node, 'remove');
            case 'clearAllHeaders':
            case 'clearRawHeaders':
                return $this->modifyHeader($node, 'replace');
            case 'removeCookie':
                return $this->modifyHeader($node, 'removeCookie');
            case 'setRawHeader':
                return $this->modifyRawHeader($node, 'set');
            case 'clearRawHeader':
                return $this->modifyRawHeader($node, 'remove');
            case 'setCookie':
                return $this->modifySetCookie($node);

            default:
                return null;
        }
    }

    private function modifySetHeader(MethodCall $methodCall): MethodCall
    {
        $methodCall->var = new PropertyFetch($methodCall->var, 'headers');
        $methodCall->name = new Identifier('set');

        if (! $methodCall->args[0]->value instanceof String_) {
            return $methodCall;
        }

        /** @var String_ $arg1 */
        $arg1 = $methodCall->args[0]->value;
        $arg1->value = strtolower($arg1->value);

        // We have a cache-control call without replace header (3rd argument)
        if ($arg1->value === 'cache-control' && ! isset($methodCall->args[2])) {
            $methodCall->args[2] = new Arg(new ConstFetch(new Name(['true'])));
        }

        return $methodCall;
    }

    private function modifyHeader(MethodCall $methodCall, string $newMethodName): MethodCall
    {
        $methodCall->var = new PropertyFetch($methodCall->var, 'headers');
        $methodCall->name = new Identifier($newMethodName);

        return $methodCall;
    }

    private function modifyRawHeader(MethodCall $methodCall, string $newMethodName): MethodCall
    {
        $methodCall->var = new PropertyFetch($methodCall->var, 'headers');
        $methodCall->name = new Identifier($newMethodName);

        if ($methodCall->args[0]->value instanceof String_) {
            $parts = $this->getRawHeaderParts($methodCall->args[0]->value->value);

            $args = [];
            foreach ($parts as $i => $part) {
                if ($i === 0) {
                    $part = strtolower($part);
                }

                $args[] = new Arg(new String_($part));
            }

            $methodCall->args = $args;
        }

        return $methodCall;
    }

    private function modifySetCookie(MethodCall $methodCall): MethodCall
    {
        $methodCall->var = new PropertyFetch($methodCall->var, 'headers');
        $methodCall->name = new Identifier('setCookie');

        $new = new New_(new FullyQualified('Symfony\Component\HttpFoundation\Cookie'), $methodCall->args);
        $methodCall->args = [new Arg($new)];

        return $methodCall;
    }

    /**
     * @return string[]
     */
    private function getRawHeaderParts(string $name): array
    {
        return array_map('trim', explode(':', $name, 2));
    }
}
