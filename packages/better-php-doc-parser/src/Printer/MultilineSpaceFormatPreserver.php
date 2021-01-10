<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\Printer;

use Nette\Utils\Strings;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use Rector\BetterPhpDocParser\Attributes\Attribute\Attribute;
use Rector\PhpdocParserPrinter\Contract\AttributeAwareInterface;
use Rector\PhpdocParserPrinter\ValueObject\PhpDocNode\AttributeAwareGenericTagValueNode;
use Rector\PhpdocParserPrinter\ValueObject\PhpDocNode\AttributeAwarePhpDocTagNode;

final class MultilineSpaceFormatPreserver
{
    /**
     * @var string
     * @see https://regex101.com/r/R2zdQt/1
     */
    public const NEWLINE_WITH_SPACE_REGEX = '#\n {1,}$#s';

    public function resolveCurrentPhpDocNodeText(Node $node): ?string
    {
        if ($node instanceof PhpDocTagNode &&
            property_exists($node->value, 'description')
        ) {
            return $node->value->description;
        }

        if ($node instanceof PhpDocTextNode) {
            return $node->text;
        }

        if (! $node instanceof PhpDocTagNode) {
            return null;
        }

        if (! $node->value instanceof GenericTagValueNode) {
            return null;
        }

        if (substr_count($node->value->value, "\n") > 0) {
            return $node->value->value;
        }

        return null;
    }

    /**
     * Fix multiline BC break - https://github.com/phpstan/phpdoc-parser/pull/26/files
     */
    public function fixMultilineDescriptions(AttributeAwareInterface $attributeAware): AttributeAwareInterface
    {
        $originalContent = $attributeAware->getAttribute(Attribute::ORIGINAL_CONTENT);
        if (! $originalContent) {
            return $attributeAware;
        }

        $nodeWithRestoredSpaces = $this->restoreOriginalSpacingInText($attributeAware);
        if ($nodeWithRestoredSpaces !== null) {
            $attributeAware = $nodeWithRestoredSpaces;
            $attributeAware->setAttribute(Attribute::HAS_DESCRIPTION_WITH_ORIGINAL_SPACES, true);
        }

        return $attributeAware;
    }

    /**
     * @param PhpDocTextNode|AttributeAwareInterface $attributeAware
     */
    private function restoreOriginalSpacingInText(
        AttributeAwareInterface $attributeAware
    ): ?AttributeAwareInterface {
        /** @var string $originalContent */
        $originalContent = $attributeAware->getAttribute(Attribute::ORIGINAL_CONTENT);
        $oldSpaces = Strings::matchAll($originalContent, '#\s+#ms');

        $currentText = $this->resolveCurrentPhpDocNodeText($attributeAware);
        if ($currentText === null) {
            return null;
        }

        $newParts = Strings::split($currentText, '#\s+#');

        // we can't do this!
        if (count($oldSpaces) + 1 !== count($newParts)) {
            return null;
        }

        $newText = '';
        foreach ($newParts as $key => $newPart) {
            $newText .= $newPart;
            if (isset($oldSpaces[$key])) {
                if (Strings::match($oldSpaces[$key][0], self::NEWLINE_WITH_SPACE_REGEX)) {
                    // remove last extra space
                    $oldSpaces[$key][0] = Strings::substring($oldSpaces[$key][0], 0, -1);
                }

                $newText .= $oldSpaces[$key][0];
            }
        }

        if ($newText === '') {
            return null;
        }

        return $this->setNewTextToPhpDocNode($attributeAware, $newText);
    }

    private function setNewTextToPhpDocNode(
        AttributeAwareInterface $attributeAware,
        string $newText
    ): AttributeAwareInterface {
        if ($attributeAware instanceof PhpDocTagNode && property_exists($attributeAware->value, 'description')) {
            $attributeAware->value->description = $newText;
        }

        if ($attributeAware instanceof PhpDocTextNode) {
            $attributeAware->text = $newText;
        }

        if ($attributeAware instanceof AttributeAwarePhpDocTagNode && $attributeAware->value instanceof AttributeAwareGenericTagValueNode) {
            $attributeAware->value->value = $newText;
        }

        return $attributeAware;
    }
}
