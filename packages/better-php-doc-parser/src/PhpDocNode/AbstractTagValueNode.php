<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocNode;

use Nette\Utils\Json;
use Nette\Utils\Strings;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use Rector\BetterPhpDocParser\Attributes\Attribute\AttributeTrait;
use Rector\BetterPhpDocParser\Contract\PhpDocNode\AttributeAwareNodeInterface;
use Rector\BetterPhpDocParser\Contract\PhpDocNode\TagAwareNodeInterface;
use Rector\BetterPhpDocParser\Utils\ArrayItemStaticHelper;

abstract class AbstractTagValueNode implements AttributeAwareNodeInterface, PhpDocTagValueNode
{
    use AttributeTrait;

    /**
     * @var bool
     */
    protected $hasNewlineAfterOpening = false;

    /**
     * @var bool
     */
    protected $hasNewlineBeforeClosing = false;

    /**
     * @var string|null
     */
    protected $originalContent;

    /**
     * @var string[]|null
     */
    protected $orderedVisibleItems;

    /**
     * @var bool
     */
    protected $hasOpeningBracket = false;

    /**
     * @var bool
     */
    protected $hasClosingBracket = false;

    /**
     * @param mixed[] $item
     */
    protected function printArrayItem(array $item, ?string $key = null): string
    {
        $json = Json::encode($item);

        // separate by space only items separated by comma, not in "quotes"
        $json = Strings::replace($json, '#,#', ', ');
        // @see https://regex101.com/r/C2fDQp/2
        $json = Strings::replace($json, '#("[^",]+)(\s+)?,(\s+)?([^"]+")#', '$1,$4');

        // change brackets from json to annotations
        $json = Strings::replace($json, '#^\[(.*?)\]$#', '{$1}');

        // cleanup json encoded extra slashes
        $json = Strings::replace($json, '#\\\\\\\\#', '\\');

        if ($key) {
            return sprintf('%s=%s', $key, $json);
        }

        return $json;
    }

    protected function printArrayItemWithoutQuotes(array $item, ?string $key = null): string
    {
        $content = $this->printArrayItem($item, $key);
        return Strings::replace($content, '#"#');
    }

    /**
     * @param mixed[] $item
     */
    protected function printArrayItemWithSeparator(array $item, ?string $key = null, string $separator = ''): string
    {
        $content = $this->printArrayItem($item, $key);

        return Strings::replace($content, '#:#', $separator);
    }

    /**
     * @param string[] $contentItems
     */
    protected function printContentItems(array $contentItems): string
    {
        if ($this->orderedVisibleItems !== null) {
            $contentItems = ArrayItemStaticHelper::filterAndSortVisibleItems($contentItems, $this->orderedVisibleItems);
        }

        if ($contentItems === []) {
            if ($this->originalContent !== null && Strings::endsWith($this->originalContent, '()')) {
                return '()';
            }

            return '';
        }

        return sprintf(
            '(%s%s%s)',
            $this->hasNewlineAfterOpening ? PHP_EOL : '',
            implode(', ', $contentItems),
            $this->hasNewlineBeforeClosing ? PHP_EOL : ''
        );
    }

    /**
     * @param PhpDocTagValueNode[] $tagValueNodes
     */
    protected function printNestedTag(
        array $tagValueNodes,
        string $label,
        bool $haveFinalComma = false,
        ?string $openingSpace = null,
        ?string $closingSpace = null
    ): string {
        $tagValueNodesAsString = $this->printTagValueNodesSeparatedByComma($tagValueNodes);

        if ($openingSpace === null) {
            $openingSpace = PHP_EOL . '    ';
        }

        if ($closingSpace === null) {
            $closingSpace = PHP_EOL;
        }

        return sprintf(
            '%s={%s%s%s%s}',
            $label,
            $openingSpace,
            $tagValueNodesAsString,
            $haveFinalComma ? ',' : '',
            $closingSpace
        );
    }

    protected function resolveOriginalContentSpacingAndOrder(?string $originalContent, ?string $silentKey = null): void
    {
        if ($originalContent === null) {
            return;
        }

        $this->originalContent = $originalContent;
        $this->orderedVisibleItems = ArrayItemStaticHelper::resolveAnnotationItemsOrder($originalContent, $silentKey);

        $this->hasNewlineAfterOpening = (bool) Strings::match($originalContent, '#^(\(\s+|\n)#m');
        $this->hasNewlineBeforeClosing = (bool) Strings::match($originalContent, '#(\s+\)|\n(\s+)?)$#m');

        $this->hasOpeningBracket = (bool) Strings::match($originalContent, '#^\(#');
        $this->hasClosingBracket = (bool) Strings::match($originalContent, '#\)$#');
    }

    protected function resolveIsValueQuoted(string $originalContent, $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (! is_string($value)) {
            return false;
        }

        // @see https://regex101.com/r/VgvK8C/3/
        $quotedNamePattern = sprintf('#"%s"#', preg_quote($value, '#'));

        return (bool) Strings::match($originalContent, $quotedNamePattern);
    }

    protected function printWithOptionalQuotes(string $name, $value, bool $isQuoted, bool $isExplicit = true): string
    {
        $content = '';
        if ($isExplicit) {
            $content = $name . '=';
        }

        if (is_array($value)) {
            return $content . $this->printArrayItem($value);
        }

        if ($isQuoted) {
            return $content . sprintf('"%s"', $value);
        }

        return $content . sprintf('%s', $value);
    }

    /**
     * @param PhpDocTagValueNode[] $tagValueNodes
     */
    private function printTagValueNodesSeparatedByComma(array $tagValueNodes): string
    {
        if ($tagValueNodes === []) {
            return '';
        }

        $itemsAsStrings = [];
        foreach ($tagValueNodes as $tagValueNode) {
            $item = '';
            if ($tagValueNode instanceof TagAwareNodeInterface) {
                $item .= $tagValueNode->getTag();
            }

            $item .= (string) $tagValueNode;

            $itemsAsStrings[] = $item;
        }

        return implode(', ', $itemsAsStrings);
    }
}
