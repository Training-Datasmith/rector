<?php

declare (strict_types=1);
namespace Rector\Value_Object\Reporting;

use Rector\Changes_Reporting\Value_Object\Rector_With_Line_Change;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Parallel\Value_Object\Bridge_Item;
use Rector\Util\Rector_Classes_Sorter;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Symplify\Easy_Parallel\Contract\Serializable_Interface;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @see \Rector\Tests\ValueObject\Reporting\FileDiffTest
 */
final class File_Diff implements Serializable_Interface
{
    /**
     * @readonly
     */
    private string $relative_file_path;
    /**
     * @readonly
     */
    private string $diff;
    /**
     * @readonly
     */
    private string $diff_console_formatted;
    /**
     * @var RectorWithLineChange[]
     * @readonly
     */
    private array $rectors_with_line_changes = [];
    /**
     * @see https://en.wikipedia.org/wiki/Diff#Unified_format
     * @see https://regex101.com/r/AUPIX4/2
     * @var string
     */
    private const DIFF_HUNK_HEADER_REGEX = '#@@(.*?)(?<' . self::FIRST_LINE_KEY . '>\d+)(,(?<' . self::LINE_RANGE_KEY . '>\d+))?(.*?)@@#';
    /**
     * @var string
     */
    private const FIRST_LINE_KEY = 'first_line';
    /**
     * @var string
     */
    private const LINE_RANGE_KEY = 'line_range';
    /**
     * @param RectorWithLineChange[] $rectorsWithLineChanges
     */
    public function __construct(string $relative_file_path, string $diff, string $diff_console_formatted, array $rectors_with_line_changes = [])
    {
        $this->relative_file_path = $relative_file_path;
        $this->diff = $diff;
        $this->diff_console_formatted = $diff_console_formatted;
        $this->rectors_with_line_changes = $rectors_with_line_changes;
        Assert::all_is_instance_of($rectors_with_line_changes, Rector_With_Line_Change::class);
    }
    public function get_diff(): string
    {
        return $this->diff;
    }
    public function get_diff_console_formatted(): string
    {
        return $this->diff_console_formatted;
    }
    public function get_relative_file_path(): string
    {
        return $this->relative_file_path;
    }
    public function get_absolute_file_path(): ?string
    {
        return \realpath($this->relative_file_path) ?: null;
    }
    /**
     * @return RectorWithLineChange[]
     */
    public function get_rector_changes(): array
    {
        return $this->rectors_with_line_changes;
    }
    /**
     * @return string[]
     */
    public function get_rector_short_classes(): array
    {
        $rector_short_classes = [];
        foreach ($this->get_rector_classes() as $rector_class) {
            $rector_short_classes[] = (string) Strings::after($rector_class, '\\', -1);
        }
        return $rector_short_classes;
    }
    /**
     * @return array<class-string<RectorInterface>>
     */
    public function get_rector_classes(): array
    {
        $rector_classes = [];
        foreach ($this->rectors_with_line_changes as $rector_with_line_change) {
            $rector_classes[] = $rector_with_line_change->get_rector_class();
        }
        return Rector_Classes_Sorter::sort_and_filter_out_post_rectors($rector_classes);
    }
    public function get_first_line_number(): ?int
    {
        $match = Strings::match($this->diff, self::DIFF_HUNK_HEADER_REGEX);
        // probably some error in diff
        if (!isset($match[self::FIRST_LINE_KEY])) {
            return null;
        }
        return (int) $match[self::FIRST_LINE_KEY];
    }
    public function get_last_line_number(): ?int
    {
        $match = Strings::match($this->diff, self::DIFF_HUNK_HEADER_REGEX);
        $first_line = $this->get_first_line_number();
        // probably some error in diff
        if (!isset($match[self::LINE_RANGE_KEY])) {
            return $first_line;
        }
        // line range is not mandatory
        if ($match[self::LINE_RANGE_KEY] === '') {
            return $first_line;
        }
        $line_range = (int) $match[self::LINE_RANGE_KEY];
        return $first_line + $line_range;
    }
    /**
     * @return array{relative_file_path: string, diff: string, diff_console_formatted: string, rectors_with_line_changes: RectorWithLineChange[]}
     */
    public function jsonSerialize(): array
    {
        return [Bridge_Item::RELATIVE_FILE_PATH => $this->relative_file_path, Bridge_Item::DIFF => $this->diff, Bridge_Item::DIFF_CONSOLE_FORMATTED => $this->diff_console_formatted, Bridge_Item::RECTORS_WITH_LINE_CHANGES => $this->rectors_with_line_changes];
    }
    /**
     * @param array<string, mixed> $json
     */
    public static function decode(array $json): self
    {
        $rector_with_line_changes = [];
        foreach ($json[Bridge_Item::RECTORS_WITH_LINE_CHANGES] as $rector_with_line_changes_json) {
            $rector_with_line_changes[] = Rector_With_Line_Change::decode($rector_with_line_changes_json);
        }
        return new self($json[Bridge_Item::RELATIVE_FILE_PATH], $json[Bridge_Item::DIFF], $json[Bridge_Item::DIFF_CONSOLE_FORMATTED], $rector_with_line_changes);
    }
}