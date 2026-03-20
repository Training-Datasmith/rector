<?php

declare (strict_types=1);
namespace Rector\Changes_Reporting\Output;

use Rector\Changes_Reporting\Contract\Output\Output_Formatter_Interface;
use Rector\Parallel\Value_Object\Bridge;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Error\System_Error;
use Rector\Value_Object\Process_Result;
use Rector_Prefix202603\Nette\Utils\Json;
final class Json_Output_Formatter implements Output_Formatter_Interface
{
    /**
     * @var string
     */
    public const NAME = 'json';
    public function get_name(): string
    {
        return self::NAME;
    }
    public function report(Process_Result $process_result, Configuration $configuration): void
    {
        $errors_json = ['totals' => ['changed_files' => $process_result->get_total_changed()]];
        $file_diffs = $process_result->get_file_diffs();
        ksort($file_diffs);
        foreach ($file_diffs as $file_diff) {
            $file_path = $configuration->is_reporting_with_real_path() ? $file_diff->get_absolute_file_path() ?? '' : $file_diff->get_relative_file_path();
            $errors_json[Bridge::FILE_DIFFS][] = ['file' => $file_path, 'diff' => $file_diff->get_diff(), 'applied_rectors' => $file_diff->get_rector_classes()];
            // for Rector CI
            $errors_json['changed_files'][] = $file_path;
        }
        $system_errors = $process_result->get_system_errors();
        $errors_json['totals']['errors'] = count($system_errors);
        $errors_data = $this->create_errors_data($system_errors, $configuration->is_reporting_with_real_path());
        if ($errors_data !== []) {
            $errors_json['errors'] = $errors_data;
        }
        $json = Json::encode($errors_json, \true);
        echo $json . \PHP_EOL;
    }
    /**
     * @param SystemError[] $errors
     * @return mixed[]
     */
    private function create_errors_data(array $errors, bool $absolute_file_path): array
    {
        $errors_data = [];
        foreach ($errors as $error) {
            $error_data_json = ['message' => $error->get_message(), 'file' => $absolute_file_path ? $error->get_absolute_file_path() : $error->get_relative_file_path()];
            if ($error->get_rector_class() !== null) {
                $error_data_json['caused_by'] = $error->get_rector_class();
            }
            if ($error->get_line() !== null) {
                $error_data_json['line'] = $error->get_line();
            }
            $errors_data[] = $error_data_json;
        }
        return $errors_data;
    }
}