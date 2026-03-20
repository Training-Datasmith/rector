<?php

declare (strict_types=1);
namespace Rector\Console\Formatter;

use Rector\Util\New_Line_Splitter;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Symfony\Component\Console\Formatter\Output_Formatter;
/**
 * Inspired by @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/Differ/DiffConsoleFormatter.php to be
 * used as standalone class, without need to require whole package by Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @see \Rector\Tests\Console\Formatter\ColorConsoleDiffFormatterTest
 */
final class Color_Console_Diff_Formatter
{
    /**
     * @see https://regex101.com/r/ovLMDF/1
     * @var string
     */
    private const PLUS_START_REGEX = '#^(\+.*)#';
    /**
     * @see https://regex101.com/r/xwywpa/1
     * @var string
     */
    private const MINUS_START_REGEX = '#^(\-.*)#';
    /**
     * @see https://regex101.com/r/CMlwa8/1
     * @var string
     */
    private const AT_START_REGEX = '#^(@.*)#';
    /**
     * @see https://regex101.com/r/8MXnfa/2
     * @var string
     */
    private const AT_DIFF_LINE_REGEX = '#^\<fg=cyan\>@@ \-\d+,\d+ \+\d+,\d+ @@\<\/fg=cyan\>$#';
    /**
     * @readonly
     */
    private string $template;
    public function __construct()
    {
        $this->template = sprintf('<comment>    ---------- begin diff ----------</comment>%s%%s%s<comment>    ----------- end diff -----------</comment>' . \PHP_EOL, \PHP_EOL, \PHP_EOL);
    }
    public function format(string $diff): string
    {
        return $this->format_with_template($diff, $this->template);
    }
    private function format_with_template(string $diff, string $template): string
    {
        $escaped_diff = Output_Formatter::escape(rtrim($diff));
        $escaped_diff_lines = New_Line_Splitter::split($escaped_diff);
        // remove description of added + remove, obvious on diffs
        // decorize lines
        foreach ($escaped_diff_lines as $key => $escaped_diff_line) {
            if ($escaped_diff_line === '--- Original') {
                unset($escaped_diff_lines[$key]);
                continue;
            }
            if ($escaped_diff_line === '+++ New') {
                unset($escaped_diff_lines[$key]);
                continue;
            }
            if ($escaped_diff_line === ' ') {
                $escaped_diff_lines[$key] = '';
                continue;
            }
            $escaped_diff_line = $this->make_plus_lines_green($escaped_diff_line);
            $escaped_diff_line = $this->make_minus_lines_red($escaped_diff_line);
            $escaped_diff_line = $this->make_at_note_cyan($escaped_diff_line);
            $escaped_diff_line = $this->normalize_line_at_diff($escaped_diff_line);
            // final decorized line
            $escaped_diff_lines[$key] = $escaped_diff_line;
        }
        return sprintf($template, implode(\PHP_EOL, $escaped_diff_lines));
    }
    /**
     * Remove number diff, eg; @@ -67,6 +67,8 @@ to become @@ @@
     */
    private function normalize_line_at_diff(string $string): string
    {
        return Strings::replace($string, self::AT_DIFF_LINE_REGEX, '<fg=cyan>@@ @@</fg=cyan>');
    }
    private function make_plus_lines_green(string $string): string
    {
        return Strings::replace($string, self::PLUS_START_REGEX, '<fg=green>$1</fg=green>');
    }
    private function make_minus_lines_red(string $string): string
    {
        return Strings::replace($string, self::MINUS_START_REGEX, '<fg=red>$1</fg=red>');
    }
    private function make_at_note_cyan(string $string): string
    {
        return Strings::replace($string, self::AT_START_REGEX, '<fg=cyan>$1</fg=cyan>');
    }
}