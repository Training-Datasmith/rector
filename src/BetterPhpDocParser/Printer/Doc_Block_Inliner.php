<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Printer;

use Rector_Prefix202603\Nette\Utils\Strings;
final class Doc_Block_Inliner
{
    /**
     * @see https://regex101.com/r/Mjb0qi/3
     * @var string
     */
    private const NEWLINE_CLOSING_DOC_REGEX = "#(?:\r\n|\n) \\*\\/\$#";
    /**
     * @see https://regex101.com/r/U5OUV4/4
     * @var string
     */
    private const NEWLINE_MIDDLE_DOC_REGEX = "#(?:\r\n|\n) \\* #";
    public function inline(string $doc_content): string
    {
        $doc_content = Strings::replace($doc_content, self::NEWLINE_MIDDLE_DOC_REGEX, ' ');
        return Strings::replace($doc_content, self::NEWLINE_CLOSING_DOC_REGEX, ' */');
    }
}