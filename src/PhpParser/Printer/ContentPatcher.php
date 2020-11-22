<?php

declare(strict_types=1);

namespace Rector\Core\PhpParser\Printer;

use Nette\Utils\Strings;

final class ContentPatcher
{
    /**
     * @seehttpshttps://regex101.com/r/cLgjQf/3
     * @var string
     */
    public const VALID_ANNOTATION_STRING_REGEX = '#\*\s+@.*".{1,}"}\)#';

    /**
     * @seehttpshttps://regex101.com/r/BhxeM8/3
     * @var string
     */
    public const INVALID_ANNOTATION_STRING_REGEX = '#\*\s+@.*.{1,}[^"]}\)#';

    /**
     * @seehttpshttps://regex101.com/r/wpVS09/1
     * @var string
     */
    public const VALID_ANNOTATION_ROUTE_REGEX = '#\*\s+@.*:\s?".{1,}"}\)#';

    /**
     * @seehttpshttps://regex101.com/r/cIgWGi/1
     * @var string
     */
    public const INVALID_ANNOTATION_ROUTE_REGEX = '#\*\s+@.*=\s?".{1,}"}\)#';

    /**
     * @seehttpshttps://regex101.com/r/nCPUz9/2
     * @var string
     */
    public const VALID_ANNOTATION_COMMENT_REGEX = '#\*\s+@.*="[^"]*"}\)#';

    /**
     * @seehttpshttps://regex101.com/r/xPg2yo/1
     * @var string
     */
    public const INVALID_ANNOTATION_COMMENT_REGEX = '#\*\s+@.*=".*"}\)#';

    /**
     * @seehttpshttps://regex101.com/r/5HT5AW/7
     * @var string
     */
    public const VALID_ANNOTATION_CONSTRAINT_REGEX = '#\*\s+@.*\(?[\s\*]{0,}.*\s{0,}={(\s{0,}\*?\s{0,}".*",?){1,}[\s*]+}[\s\*]{1,}\)[\s\*}\)]{0,}#';

    /**
     * @seehttpshttps://regex101.com/r/U8KzfW/7
     * @var string
     */
    public const INVALID_ANNOTATION_CONSTRAINT_REGEX = '#\*\s+@.*\(?[\s\*]{0,}.*\s{0,}={[^"].*(,[\s+\*]+.*)?}[\s\*]{1,}\)[\s\*}\)]{0,}#';

    /**
     * @seehttpshttps://regex101.com/r/rbCG9a/3
     * @var string
     */
    public const VALID_ANNOTATION_ROUTE_OPTION_REGEX = '#\*\s+@.*={(\s{0,}".*"\s{0,}=\s{0,}[^",]*\s{0,},?){1,}}.*\)#';

    /**
     * @seehttpshttps://regex101.com/r/Kl3Ot1/3
     * @var string
     */
    public const INVALID_ANNOTATION_ROUTE_OPTION_REGEX = '#\*\s+@.*={([^"]*=[^"]*,?){1,}[^,]}.*\)#';

    /**
     * @seehttpshttps://regex101.com/r/Hm2idk/1
     * @var string
     */
    public const VALID_ANNOTATION_ROUTE_LOCALIZATION_REGEX = '#^\s+\/\*\*\s+\s+\*\s+@.*\({(\s+\*\s{0,}".*":\s{0,}".*",?){1,}\s+\*\s{0,}[^,]}.*\)\s+\*\/#msU';

    /**
     * @seehttpshttps://regex101.com/r/qVOGbC/2
     * @var string
     */
    public const INVALID_ANNOTATION_ROUTE_LOCALIZATION_REGEX = '#^\s+\/\*\*\s+\s+\*\s+@.*(\s+\*\s{0,}[^"]*=\s{0,}[^"]*,?){1,}.*\)\s+\*\s+\*\/#msU';

    /**
     * @seehttpshttps://regex101.com/r/EA1xRY/2
     * @var string
     */
    public const VALID_ANNOTATION_RETURN_EXPLICIT_FORMAT_REGEX = '#^\s{0,}\* @return\s+(\(.*\)|(".*")(\|".*"){1,})$#msU';

    /**
     * @seehttpshttps://regex101.com/r/LprF44/3
     * @var string
     */
    public const INVALID_ANNOTATION_RETURN_EXPLICIT_FORMAT_REGEX = '#^\s{0,}\* @return([^\s].*|\s[^"\s]*)$#msU';

    /**
     * @seehttpshttps://regex101.com/r/4mBd0y/2
     * @var string
     */
    private const CODE_MAY_DUPLICATE_REGEX = '#(if\s{0,}\(%s\(.*\{\s{0,}.*\s{0,}\}){2}#';

    /**
     * @seehttpshttps://regex101.com/r/k48bUj/1
     * @var string
     */
    private const CODE_MAY_DUPLICATE_NO_BRACKET_REGEX = '#(if\s{0,}\(%s\(.*\s{1,}.*\s{0,}){2}#';

    /**
     * @seehttpshttps://regex101.com/r/Ef83BV/1
     * @var string
     */
    private const SPACE_REGEX = '#\s#';

    /**
     * @seehttpshttps://regex101.com/r/lC0i21/2
     * @var string
     */
    private const STAR_QUOTE_PARENTHESIS_REGEX = '#[\*"\(\)]#';

    /**
     * @seehttpshttps://regex101.com/r/j7agVx/1
     * @var string
     */
    private const ROUTE_VALID_REGEX = '#"\s?:\s?#';

    /**
     * @seehttpshttps://regex101.com/r/qgp6Tr/1
     * @var string
     */
    private const ROUTE_INVALID_REGEX = '#"\s?=\s?#';

    /**
     * @var string
     * @seehttpshttps://regex101.com/r/5DdLjE/1
     */
    private const ROUTE_LOCALIZATION_REPLACE_REGEX = '#[:=]#';

    /**
     * @var string[]
     */
    private const MAY_DUPLICATE_FUNC_CALLS = ['interface_exists', 'trait_exists'];

    /**
     * @seehttpshttps://github.com/rectorphp/rector/issues/4499
     */
    public function cleanUpDuplicateContent(string $content): string
    {
        foreach (self::MAY_DUPLICATE_FUNC_CALLS as $mayDuplicateFuncCall) {
            $matches = Strings::match($content, sprintf(self::CODE_MAY_DUPLICATE_REGEX, $mayDuplicateFuncCall));

            if ($matches === null) {
                $matches = Strings::match(
                    $content,
                    sprintf(self::CODE_MAY_DUPLICATE_NO_BRACKET_REGEX, $mayDuplicateFuncCall)
                );
            }

            if ($matches === null) {
                continue;
            }
            $secondMatch = Strings::replace($matches[1], self::SPACE_REGEX, '');
            $firstMatch = Strings::replace($matches[0], self::SPACE_REGEX, '');

            if ($firstMatch === str_repeat($secondMatch, 2)) {
                $content = str_replace($matches[0], $matches[1], $content);
            }
        }

        return $content;
    }

    /**
     * @seehttpshttps://github.com/rectorphp/rector/issues/3388
     * @seehttpshttps://github.com/rectorphp/rector/issues/3673
     * @seehttpshttps://github.com/rectorphp/rector/issues/4274
     * @seehttpshttps://github.com/rectorphp/rector/issues/4573
     * @seehttpshttps://github.com/rectorphp/rector/issues/4581
     * @seehttpshttps://github.com/rectorphp/rector/issues/4476
     * @seehttpshttps://github.com/rectorphp/rector/issues/4620
     * @seehttpshttps://github.com/rectorphp/rector/issues/4652
     */
    public function rollbackValidAnnotation(
        string $originalContent,
        string $content,
        string $validAnnotationRegex,
        string $invalidAnnotationRegex
    ): string {
        $matchesValidAnnotation = Strings::matchAll($originalContent, $validAnnotationRegex);
        if ($matchesValidAnnotation === []) {
            return $content;
        }

        $matchesInValidAnnotation = Strings::matchAll($content, $invalidAnnotationRegex);
        if ($matchesInValidAnnotation === []) {
            return $content;
        }

        if (count($matchesValidAnnotation) !== count($matchesInValidAnnotation)) {
            return $content;
        }

        foreach ($matchesValidAnnotation as $key => $match) {
            $validAnnotation = $match[0];
            $invalidAnnotation = $matchesInValidAnnotation[$key][0];

            if ($this->isSkipped($validAnnotationRegex, $validAnnotation, $invalidAnnotation)) {
                continue;
            }

            $content = str_replace($invalidAnnotation, $validAnnotation, $content);
        }

        return $content;
    }

    private function isSkipped(string $validAnnotationRegex, string $validAnnotation, string $invalidAnnotation): bool
    {
        $validAnnotation = Strings::replace($validAnnotation, self::SPACE_REGEX, '');
        $invalidAnnotation = Strings::replace($invalidAnnotation, self::SPACE_REGEX, '');

        if ($validAnnotationRegex !== self::VALID_ANNOTATION_ROUTE_REGEX) {
            $validAnnotation = Strings::replace($validAnnotation, self::STAR_QUOTE_PARENTHESIS_REGEX, '');
            $invalidAnnotation = Strings::replace($invalidAnnotation, self::STAR_QUOTE_PARENTHESIS_REGEX, '');

            if ($validAnnotationRegex === self::VALID_ANNOTATION_ROUTE_LOCALIZATION_REGEX) {
                $validAnnotation = Strings::replace($validAnnotation, self::ROUTE_LOCALIZATION_REPLACE_REGEX, '');
                $invalidAnnotation = Strings::replace($invalidAnnotation, self::ROUTE_LOCALIZATION_REPLACE_REGEX, '');
            }

            return $validAnnotation !== $invalidAnnotation;
        }

        $validAnnotation = Strings::replace($validAnnotation, self::ROUTE_VALID_REGEX, '');
        $invalidAnnotation = Strings::replace($invalidAnnotation, self::ROUTE_INVALID_REGEX, '');

        return $validAnnotation !== $invalidAnnotation;
    }
}
