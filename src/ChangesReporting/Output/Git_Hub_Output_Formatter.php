<?php

/**
 * Reports errors in pull-requests diff when run in a GitHub Action
 * @see https://help.github.com/en/actions/reference/workflow-commands-for-github-actions#setting-an-error-message
 * @see https://github.com/actions/toolkit/blob/main/packages/core/src/command.ts
 *
 * @todo: print endLine property. For now in GitHub’s PR display (specifically in GitHub Actions annotations), the "endLine" property is known to be bugged.
 * @see https://github.com/orgs/community/discussions/129899
 */
declare (strict_types=1);
namespace Rector\Changes_Reporting\Output;

use Rector\Changes_Reporting\Contract\Output\Output_Formatter_Interface;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Process_Result;
/**
 * @phpstan-type AnnotationProperties array{title?: string|null, file?: string|null, col?: int|null, endColumn?: int|null, line?: int|null, endLine?: int|null}
 * @see \Rector\Tests\ChangesReporting\Output\GitHubOutputFormatterTest
 */
final class Git_Hub_Output_Formatter implements Output_Formatter_Interface
{
    /**
     * @var string
     */
    private const NAME = 'github';
    /**
     * @var string
     */
    private const GROUP_NAME = 'Rector report';
    public function get_name(): string
    {
        return self::NAME;
    }
    public function report(Process_Result $process_result, Configuration $configuration): void
    {
        $this->start_group();
        $this->report_system_errors($process_result, $configuration);
        $this->report_file_diffs($process_result, $configuration);
        $this->end_group();
    }
    private function start_group(): void
    {
        echo sprintf('::group::%s', self::GROUP_NAME) . \PHP_EOL;
    }
    private function end_group(): void
    {
        echo '::endgroup::' . \PHP_EOL;
    }
    private function report_system_errors(Process_Result $process_result, Configuration $configuration): void
    {
        foreach ($process_result->get_system_errors() as $system_error) {
            $file_path = $configuration->is_reporting_with_real_path() ? $system_error->get_absolute_file_path() : $system_error->get_relative_file_path();
            $line = $system_error->get_line();
            $message = trim($system_error->get_rector_short_class() . \PHP_EOL . $system_error->get_message());
            $this->report_error_annotation($message, ['file' => $file_path, 'line' => $line]);
        }
    }
    private function report_file_diffs(Process_Result $process_result, Configuration $configuration): void
    {
        $file_diffs = $process_result->get_file_diffs();
        ksort($file_diffs);
        foreach ($file_diffs as $file_diff) {
            $file_path = $configuration->is_reporting_with_real_path() ? $file_diff->get_absolute_file_path() : $file_diff->get_relative_file_path();
            $line = $file_diff->get_first_line_number();
            $end_line = $file_diff->get_last_line_number();
            $message = trim(implode(' / ', $file_diff->get_rector_short_classes())) . \PHP_EOL . \PHP_EOL . $file_diff->get_diff();
            $this->report_error_annotation($message, ['file' => $file_path, 'line' => $line, 'endLine' => $end_line]);
        }
    }
    /**
     * @param AnnotationProperties $annotationProperties
     */
    private function report_error_annotation(string $message, array $annotation_properties): void
    {
        $properties = $this->sanitize_annotation_properties($annotation_properties);
        $command = sprintf('::error %s::%s', $properties, $message);
        // Sanitize command
        $command = str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $command);
        echo $command . \PHP_EOL;
    }
    /**
     * @param AnnotationProperties $annotationProperties
     */
    private function sanitize_annotation_properties(array $annotation_properties): string
    {
        if (!isset($annotation_properties['line']) || !$annotation_properties['line']) {
            $annotation_properties['line'] = 0;
        }
        // This is a workaround for buggy endLine. See https://github.com/orgs/community/discussions/129899
        // TODO: Should be removed once github will have fixed it issue.
        unset($annotation_properties['endLine']);
        $non_null_properties = array_filter($annotation_properties, static fn($value): bool => $value !== null);
        $sanitized_properties = array_map(fn(string $key, $value): string => sprintf('%s=%s', $key, $this->sanitize_annotation_property($value)), array_keys($non_null_properties), $non_null_properties);
        return implode(',', $sanitized_properties);
    }
    /**
     * @param string|int|null $value
     */
    private function sanitize_annotation_property($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $value = (string) $value;
        return str_replace(['%', "\r", "\n", ':', ','], ['%25', '%0D', '%0A', '%3A', '%2C'], $value);
    }
}