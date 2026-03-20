<?php

/**
 * CodeClimate Specification:
 * - https://github.com/codeclimate/platform/blob/master/spec/analyzers/SPEC.md
 */
declare (strict_types=1);
namespace Rector\Changes_Reporting\Output;

use Rector\Changes_Reporting\Contract\Output\Output_Formatter_Interface;
use Rector\Util\File_Hasher;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Process_Result;
use Rector_Prefix202603\Nette\Utils\Json;
final class Gitlab_Output_Formatter implements Output_Formatter_Interface
{
    /**
     * @readonly
     */
    private File_Hasher $filehasher;
    /**
     * @var string
     */
    private const NAME = 'gitlab';
    /**
     * @var string
     */
    private const ERROR_TYPE_ISSUE = 'issue';
    /**
     * @var string
     */
    private const ERROR_CATEGORY_BUG_RISK = 'Bug Risk';
    /**
     * @var string
     */
    private const ERROR_CATEGORY_STYLE = 'Style';
    /**
     * @var string
     */
    private const ERROR_SEVERITY_BLOCKER = 'blocker';
    /**
     * @var string
     */
    private const ERROR_SEVERITY_MINOR = 'minor';
    public function __construct(File_Hasher $filehasher)
    {
        $this->filehasher = $filehasher;
    }
    public function get_name(): string
    {
        return self::NAME;
    }
    public function report(Process_Result $process_result, Configuration $configuration): void
    {
        $errors_json = array_merge($this->append_system_errors($process_result, $configuration), $this->append_file_diffs($process_result, $configuration));
        $json = Json::encode($errors_json, \true);
        echo $json . \PHP_EOL;
    }
    /**
     * @return array<array{
     *      type: 'issue',
     *      categories: array{'Bug Risk'},
     *      severity: 'blocker',
     *      description: string,
     *      check_name: string,
     *      location: array{
     *          path: string,
     *          lines: array{
     *              begin: int,
     *          },
     *      },
     *  }>
     */
    private function append_system_errors(Process_Result $process_result, Configuration $configuration): array
    {
        $errors_json = [];
        foreach ($process_result->get_system_errors() as $system_error) {
            $file_path = $configuration->is_reporting_with_real_path() ? $system_error->get_absolute_file_path() ?? '' : $system_error->get_relative_file_path() ?? '';
            $fingerprint = $this->filehasher->hash($file_path . ';' . $system_error->get_line() . ';' . $system_error->get_message());
            $errors_json[] = ['fingerprint' => $fingerprint, 'type' => self::ERROR_TYPE_ISSUE, 'categories' => [self::ERROR_CATEGORY_BUG_RISK], 'severity' => self::ERROR_SEVERITY_BLOCKER, 'description' => $system_error->get_message(), 'check_name' => $system_error->get_rector_class() ?? '', 'location' => ['path' => $file_path, 'lines' => ['begin' => $system_error->get_line() ?? 0]]];
        }
        return $errors_json;
    }
    /**
     * @return array<array{
     *      type: 'issue',
     *      categories: array{'Style'},
     *      description: string,
     *      check_name: string,
     *      location: array{
     *          path: string,
     *          lines: array{
     *              begin: int,
     *          },
     *      },
     *  }>
     */
    private function append_file_diffs(Process_Result $process_result, Configuration $configuration): array
    {
        $errors_json = [];
        $file_diffs = $process_result->get_file_diffs();
        ksort($file_diffs);
        foreach ($file_diffs as $file_diff) {
            $file_path = $configuration->is_reporting_with_real_path() ? $file_diff->get_absolute_file_path() ?? '' : $file_diff->get_relative_file_path() ?? '';
            $rector_classes = implode(' / ', $file_diff->get_rector_short_classes());
            $fingerprint = $this->filehasher->hash($file_path . ';' . $file_diff->get_diff());
            $errors_json[] = ['fingerprint' => $fingerprint, 'type' => self::ERROR_TYPE_ISSUE, 'categories' => [self::ERROR_CATEGORY_STYLE], 'severity' => self::ERROR_SEVERITY_MINOR, 'description' => $rector_classes, 'content' => ['body' => $file_diff->get_diff()], 'check_name' => $rector_classes, 'location' => ['path' => $file_path, 'lines' => ['begin' => $file_diff->get_first_line_number() ?? 0]]];
        }
        return $errors_json;
    }
}