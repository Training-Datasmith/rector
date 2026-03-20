<?php

declare (strict_types=1);
namespace Rector\Changes_Reporting\Value_Object_Factory;

use Rector\Changes_Reporting\Value_Object\Rector_With_Line_Change;
use Rector\Console\Formatter\Color_Console_Diff_Formatter;
use Rector\Differ\Default_Differ;
use Rector\File_System\File_Path_Helper;
use Rector\Value_Object\Application\File;
use Rector\Value_Object\Reporting\File_Diff;
final class File_Diff_Factory
{
    /**
     * @readonly
     */
    private Default_Differ $default_differ;
    /**
     * @readonly
     */
    private File_Path_Helper $file_path_helper;
    /**
     * @readonly
     */
    private Color_Console_Diff_Formatter $color_console_diff_formatter;
    public function __construct(Default_Differ $default_differ, File_Path_Helper $file_path_helper, Color_Console_Diff_Formatter $color_console_diff_formatter)
    {
        $this->default_differ = $default_differ;
        $this->file_path_helper = $file_path_helper;
        $this->color_console_diff_formatter = $color_console_diff_formatter;
    }
    /**
     * @param RectorWithLineChange[] $rectorsWithLineChanges
     */
    public function create_file_diff_with_line_changes(bool $should_show_diffs, File $file, string $old_content, string $new_content, array $rectors_with_line_changes): File_Diff
    {
        $relative_file_path = $this->file_path_helper->relative_path($file->get_file_path());
        $diff = $should_show_diffs ? $this->default_differ->diff($old_content, $new_content) : '';
        $console_diff = $should_show_diffs ? $this->color_console_diff_formatter->format($diff) : '';
        // always keep the most recent diff
        return new File_Diff($relative_file_path, $diff, $console_diff, $rectors_with_line_changes);
    }
}