<?php

declare (strict_types=1);
namespace Rector\Util;

use Rector_Prefix202603\Nette\Utils\Strings;
final class New_Line_Splitter
{
    /**
     * @see https://regex101.com/r/qduj2O/4
     * @var string
     */
    private const NEWLINES_REGEX = "#\r?\n#";
    /**
     * @return string[]
     */
    public static function split(string $content): array
    {
        return Strings::split($content, self::NEWLINES_REGEX);
    }
}