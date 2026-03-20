<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Parser;

use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Binary_Op\Concat;
use Php_Parser\Node\Scalar\Interpolated_String;
use Php_Parser\Node\Scalar\String_;
use Php_Parser\Node\Stmt;
use Rector\Php_Parser\Node\Value\Value_Resolver;
use Rector\Php_Parser\Printer\Better_Standard_Printer;
use Rector\Util\String_Utils;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Nette\Utils\Strings;
final class Inline_Code_Parser
{
    /**
     * @readonly
     */
    private Better_Standard_Printer $better_standard_printer;
    /**
     * @readonly
     */
    private \Rector\Php_Parser\Parser\Simple_Php_Parser $simple_php_parser;
    /**
     * @readonly
     */
    private Value_Resolver $value_resolver;
    /**
     * @see https://regex101.com/r/dwe4OW/1
     * @var string
     */
    private const PRESLASHED_DOLLAR_REGEX = '#\\\\\\$#';
    /**
     * @see https://regex101.com/r/tvwhWq/1
     * @var string
     */
    private const CURLY_BRACKET_WRAPPER_REGEX = "#'{(\\\$.*?)}'#";
    /**
     * @see https://regex101.com/r/TBlhoR/1
     * @var string
     */
    private const OPEN_PHP_TAG_REGEX = '#^\<\?php\s+#';
    /**
     * @see https://regex101.com/r/TUWwKw/1/
     * @var string
     */
    private const ENDING_SEMI_COLON_REGEX = '#;(\s+)?$#';
    /**
     * @see https://regex101.com/r/8fDjnR/1
     * @var string
     */
    private const VARIABLE_IN_SINGLE_QUOTED_REGEX = '#\'(?<variable>\$.*)\'#U';
    /**
     * @see https://regex101.com/r/1lzQZv/1
     * @var string
     */
    private const BACKREFERENCE_NO_QUOTE_REGEX = '#(?<!")(?<backreference>\\\\\\d+)(?!")#';
    /**
     * @see https://regex101.com/r/nSO3Eq/1
     * @var string
     */
    private const BACKREFERENCE_NO_DOUBLE_QUOTE_START_REGEX = '#(?<!")(?<backreference>\$\d+)#';
    /**
     * @see https://regex101.com/r/13mVVg/1
     * @var string
     */
    private const HEX_BACKREFERENCE_REGEX = '#0x(?<backreference>\$\d+)#';
    public function __construct(Better_Standard_Printer $better_standard_printer, \Rector\Php_Parser\Parser\Simple_Php_Parser $simple_php_parser, Value_Resolver $value_resolver)
    {
        $this->better_standard_printer = $better_standard_printer;
        $this->simple_php_parser = $simple_php_parser;
        $this->value_resolver = $value_resolver;
    }
    /**
     * @api downgrade
     *
     * @return Stmt[]
     */
    public function parse_file(string $file_name): array
    {
        $file_content = File_System::read($file_name);
        return $this->parse_code($file_content);
    }
    /**
     * @return Stmt[]
     */
    public function parse_string(string $file_content): array
    {
        return $this->parse_code($file_content);
    }
    public function stringify(Expr $expr): string
    {
        if ($expr instanceof String_) {
            if (strpos($expr->value, "'") === \false && strpos($expr->value, '"') === \false && String_Utils::is_match($expr->value, self::HEX_BACKREFERENCE_REGEX)) {
                return Strings::replace($expr->value, self::HEX_BACKREFERENCE_REGEX, static function (array $match): string {
                    $number = ltrim((string) $match['backreference'], '\$');
                    return 'hexdec($matches[' . $number . '])';
                });
            }
            if (!String_Utils::is_match($expr->value, self::BACKREFERENCE_NO_QUOTE_REGEX)) {
                return Strings::replace($expr->value, self::BACKREFERENCE_NO_DOUBLE_QUOTE_START_REGEX, static fn(array $match): string => '"' . $match['backreference'] . '"');
            }
            return Strings::replace($expr->value, self::BACKREFERENCE_NO_QUOTE_REGEX, static fn(array $match): string => '"\\' . $match['backreference'] . '"');
        }
        if ($expr instanceof Interpolated_String) {
            return $this->resolve_encapsed_value($expr);
        }
        if ($expr instanceof Concat) {
            return $this->resolve_concat_value($expr);
        }
        return $this->better_standard_printer->print($expr);
    }
    /**
     * @return Stmt[]
     */
    private function parse_code(string $code): array
    {
        // wrap code so php-parser can interpret it
        $code = String_Utils::is_match($code, self::OPEN_PHP_TAG_REGEX) ? $code : '<?php ' . $code;
        $code = String_Utils::is_match($code, self::ENDING_SEMI_COLON_REGEX) ? $code : $code . ';';
        return $this->simple_php_parser->parse_string($code);
    }
    private function resolve_encapsed_value(Interpolated_String $interpolated_string): string
    {
        $value = '';
        $is_require_print = \false;
        foreach ($interpolated_string->parts as $part) {
            $part_value = (string) $this->value_resolver->get_value($part);
            if (substr_compare($part_value, "'", -strlen("'")) === 0) {
                $is_require_print = \true;
                break;
            }
            $value .= $part_value;
        }
        $printed_expr = $is_require_print ? $this->better_standard_printer->print($interpolated_string) : $value;
        // remove "
        $printed_expr = trim($printed_expr, '""');
        // use \$ → $
        $printed_expr = Strings::replace($printed_expr, self::PRESLASHED_DOLLAR_REGEX, '$');
        // use \'{$...}\' → $...
        return Strings::replace($printed_expr, self::CURLY_BRACKET_WRAPPER_REGEX, '$1');
    }
    private function resolve_concat_value(Concat $concat): string
    {
        if ($concat->left instanceof Concat && $concat->right instanceof String_ && strncmp($concat->right->value, '$', strlen('$')) === 0) {
            $concat->right->value = '.' . $concat->right->value;
        }
        if ($concat->right instanceof String_ && strncmp($concat->right->value, '($', strlen('($')) === 0) {
            $concat->right->value .= '.';
        }
        $string = $this->stringify($concat->left) . $this->stringify($concat->right);
        return Strings::replace($string, self::VARIABLE_IN_SINGLE_QUOTED_REGEX, static fn(array $match): string => (string) $match['variable']);
    }
}