<?php

declare (strict_types=1);
namespace Rector\Changes_Reporting\Output;

use Rector\Changes_Reporting\Contract\Output\Output_Formatter_Interface;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Error\System_Error;
use Rector\Value_Object\Process_Result;
use Rector\Value_Object\Reporting\File_Diff;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Symfony\Component\Console\Formatter\Output_Formatter;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class Console_Output_Formatter implements Output_Formatter_Interface
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @var string
     */
    public const NAME = 'console';
    /**
     * @see https://regex101.com/r/q8I66g/1
     * @var string
     */
    private const ON_LINE_REGEX = '# on line #';
    public function __construct(Symfony_Style $symfony_style)
    {
        $this->symfony_style = $symfony_style;
    }
    public function report(Process_Result $process_result, Configuration $configuration): void
    {
        if ($configuration->should_show_diffs()) {
            $this->report_file_diffs($process_result->get_file_diffs(), $configuration->is_reporting_with_real_path());
        }
        $this->report_errors($process_result->get_system_errors(), $configuration->is_reporting_with_real_path());
        if ($process_result->get_system_errors() !== []) {
            return;
        }
        // to keep space between progress bar and success message
        if ($configuration->should_show_progress_bar() && $process_result->get_file_diffs() === []) {
            $this->symfony_style->new_line();
        }
        $message = $this->create_success_message($process_result, $configuration);
        $this->symfony_style->success($message);
    }
    public function get_name(): string
    {
        return self::NAME;
    }
    /**
     * @param FileDiff[] $fileDiffs
     */
    private function report_file_diffs(array $file_diffs, bool $absolute_file_path): void
    {
        if (count($file_diffs) <= 0) {
            return;
        }
        // normalize
        ksort($file_diffs);
        $message = sprintf('%d file%s with changes', count($file_diffs), count($file_diffs) === 1 ? '' : 's');
        $this->symfony_style->title($message);
        $i = 0;
        foreach ($file_diffs as $file_diff) {
            $file_path = $absolute_file_path ? $file_diff->get_absolute_file_path() ?? '' : $file_diff->get_relative_file_path();
            // append line number for faster file jump in diff
            $first_line_number = $file_diff->get_first_line_number();
            if ($first_line_number !== null) {
                $file_path .= ':' . $first_line_number;
            }
            $file_path_with_url = $this->add_editor_url($file_path, $file_diff->get_absolute_file_path(), $file_diff->get_relative_file_path(), (string) $file_diff->get_first_line_number());
            $message = sprintf('<options=bold>%d) %s</>', ++$i, $file_path_with_url);
            $this->symfony_style->writeln($message);
            $this->symfony_style->new_line();
            $this->symfony_style->writeln($file_diff->get_diff_console_formatted());
            if ($file_diff->get_rector_changes() !== []) {
                $this->symfony_style->writeln('<options=underscore>Applied rules:</>');
                $this->symfony_style->listing($file_diff->get_rector_short_classes());
                $this->symfony_style->new_line();
            }
        }
    }
    /**
     * @param SystemError[] $errors
     */
    private function report_errors(array $errors, bool $absolute_file_path): void
    {
        foreach ($errors as $error) {
            $error_message = $error->get_message();
            $error_message = $this->normalize_paths_to_relative_with_line($error_message);
            $error_message = str_replace("\r\n", "\n", $error_message);
            $file_path = $absolute_file_path ? $error->get_absolute_file_path() : $error->get_relative_file_path();
            $message = sprintf('Could not process %s%s, due to: %s"%s".', $file_path !== null ? '"' . $file_path . '" file' : 'some files', $error->get_rector_class() !== null ? ' by "' . $error->get_rector_class() . '"' : '', "\n", $error_message);
            if ($error->get_line() !== null) {
                $message .= ' On line: ' . $error->get_line();
            }
            $this->symfony_style->error($message);
        }
    }
    private function normalize_paths_to_relative_with_line(string $error_message): string
    {
        $regex = '#' . preg_quote(getcwd(), '#') . '/#';
        $error_message = Strings::replace($error_message, $regex);
        return Strings::replace($error_message, self::ON_LINE_REGEX);
    }
    private function create_success_message(Process_Result $process_result, Configuration $configuration): string
    {
        $change_count = $process_result->get_total_changed();
        if ($change_count === 0) {
            return 'Rector is done!';
        }
        return sprintf('%d file%s %s by Rector', $change_count, $change_count > 1 ? 's' : '', $configuration->is_dry_run() ? 'would have been changed (dry-run)' : ($change_count === 1 ? 'has' : 'have') . ' been changed');
    }
    private function add_editor_url(string $file_path, ?string $absolute_file_path, ?string $relative_file_path, ?string $line_number): string
    {
        $editor_url = Simple_Parameter_Provider::provide_string_parameter(Option::EDITOR_URL, '');
        if ($editor_url !== '') {
            $editor_url = str_replace(['%file%', '%relFile%', '%line%'], [(string) $absolute_file_path, (string) $relative_file_path, (string) $line_number], $editor_url);
            $file_path = '<href=' . Output_Formatter::escape($editor_url) . '>' . $file_path . '</>';
        }
        return $file_path;
    }
}