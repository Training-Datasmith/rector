<?php

declare (strict_types=1);
namespace Rector\Application;

use Php_Stan\Parser\Parser_Errors_Exception;
use Rector\Application\Provider\Current_File_Provider;
use Rector\Caching\Detector\Changed_Files_Detector;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\File_System\Files_Finder;
use Rector\Parallel\Application\Parallel_File_Processor;
use Rector\Php_Parser\Parser\Parser_Errors;
use Rector\Reporting\Miss_Configuration_Reporter;
use Rector\Testing\Php_Unit\Static_Php_Unit_Environment;
use Rector\Util\Array_Parameters_Merger;
use Rector\Value_Object\Application\File;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Error\System_Error;
use Rector\Value_Object\File_Process_Result;
use Rector\Value_Object\Process_Result;
use Rector\Value_Object\Reporting\File_Diff;
use Rector_Prefix202603\Nette\Utils\File_System as UtilsFileSystem;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use Rector_Prefix202603\Symplify\Easy_Parallel\Cpu_Core_Count_Provider;
use Rector_Prefix202603\Symplify\Easy_Parallel\Exception\Parallel_Should_Not_Happen_Exception;
use Rector_Prefix202603\Symplify\Easy_Parallel\Schedule_Factory;
use Throwable;
final class Application_File_Processor
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @readonly
     */
    private Files_Finder $files_finder;
    /**
     * @readonly
     */
    private Parallel_File_Processor $parallel_file_processor;
    /**
     * @readonly
     */
    private Schedule_Factory $schedule_factory;
    /**
     * @readonly
     */
    private Cpu_Core_Count_Provider $cpu_core_count_provider;
    /**
     * @readonly
     */
    private Changed_Files_Detector $changed_files_detector;
    /**
     * @readonly
     */
    private Current_File_Provider $current_file_provider;
    /**
     * @readonly
     */
    private \Rector\Application\File_Processor $file_processor;
    /**
     * @readonly
     */
    private Array_Parameters_Merger $array_parameters_merger;
    /**
     * @readonly
     */
    private Miss_Configuration_Reporter $miss_configuration_reporter;
    /**
     * @var string
     */
    private const ARGV = 'argv';
    /**
     * @var SystemError[]
     */
    private array $system_errors = [];
    public function __construct(Symfony_Style $symfony_style, Files_Finder $files_finder, Parallel_File_Processor $parallel_file_processor, Schedule_Factory $schedule_factory, Cpu_Core_Count_Provider $cpu_core_count_provider, Changed_Files_Detector $changed_files_detector, Current_File_Provider $current_file_provider, \Rector\Application\File_Processor $file_processor, Array_Parameters_Merger $array_parameters_merger, Miss_Configuration_Reporter $miss_configuration_reporter)
    {
        $this->symfony_style = $symfony_style;
        $this->files_finder = $files_finder;
        $this->parallel_file_processor = $parallel_file_processor;
        $this->schedule_factory = $schedule_factory;
        $this->cpu_core_count_provider = $cpu_core_count_provider;
        $this->changed_files_detector = $changed_files_detector;
        $this->current_file_provider = $current_file_provider;
        $this->file_processor = $file_processor;
        $this->array_parameters_merger = $array_parameters_merger;
        $this->miss_configuration_reporter = $miss_configuration_reporter;
    }
    public function run(Configuration $configuration, Input_Interface $input): Process_Result
    {
        $file_paths = $this->files_finder->find_files_in_paths($configuration->get_paths(), $configuration);
        // no files found
        if ($file_paths === []) {
            return new Process_Result([], [], 0);
        }
        $this->miss_configuration_reporter->report_vendor_in_paths($file_paths);
        $this->miss_configuration_reporter->report_start_with_short_open_tag();
        $this->configure_custom_error_handler();
        /**
         * Mimic @see https://github.com/phpstan/phpstan-src/blob/ab154e1da54d42fec751e17a1199b3e07591e85e/src/Command/AnalyseApplication.php#L188C23-L244
         */
        if ($configuration->should_show_progress_bar()) {
            $file_count = count($file_paths);
            $this->symfony_style->progress_start($file_count);
            $this->symfony_style->progress_advance(0);
            $post_file_callback = function (int $step_count): void {
                $this->symfony_style->progress_advance($step_count);
                // running in parallel here → nothing else to do
            };
        } else {
            $post_file_callback = static function (int $step_count): void {
            };
        }
        if ($configuration->is_debug()) {
            $pre_file_callback = function (string $file_path): void {
                $this->symfony_style->writeln('[file] ' . $file_path);
            };
        } else {
            $pre_file_callback = null;
        }
        if ($configuration->is_parallel()) {
            $process_result = $this->run_parallel($file_paths, $input, $post_file_callback);
        } else {
            $process_result = $this->process_files($file_paths, $configuration, $pre_file_callback, $post_file_callback);
        }
        $process_result->add_system_errors($this->system_errors);
        $this->restore_error_handler();
        return $process_result;
    }
    /**
     * @param string[] $filePaths
     * @param callable(string $file): void|null $preFileCallback
     * @param callable(int $fileCount): void|null $postFileCallback
     */
    public function process_files(array $file_paths, Configuration $configuration, ?callable $pre_file_callback = null, ?callable $post_file_callback = null): Process_Result
    {
        /** @var SystemError[] $systemErrors */
        $system_errors = [];
        /** @var FileDiff[] $fileDiffs */
        $file_diffs = [];
        $total_changed = 0;
        foreach ($file_paths as $file_path) {
            if ($pre_file_callback !== null) {
                $pre_file_callback($file_path);
            }
            $file = new File($file_path, Utils_File_System::read($file_path));
            try {
                $file_process_result = $this->process_file($file, $configuration);
                $system_errors = $this->array_parameters_merger->merge($system_errors, $file_process_result->get_system_errors());
                $current_file_diff = $file_process_result->get_file_diff();
                if ($current_file_diff instanceof File_Diff) {
                    $file_diffs[] = $current_file_diff;
                }
                // progress bar on parallel handled on runParallel()
                if (is_callable($post_file_callback)) {
                    $post_file_callback(1);
                }
                if ($file_process_result->has_changed()) {
                    ++$total_changed;
                }
            } catch (Throwable $throwable) {
                $this->changed_files_detector->invalidate_file($file_path);
                if (Static_Php_Unit_Environment::is_php_unit_run()) {
                    throw $throwable;
                }
                $system_errors[] = $this->resolve_system_error($throwable, $file_path);
            }
        }
        return new Process_Result($system_errors, $file_diffs, $total_changed);
    }
    private function process_file(File $file, Configuration $configuration): File_Process_Result
    {
        $this->current_file_provider->set_file($file);
        $file_process_result = $this->file_processor->process_file($file, $configuration);
        if ($file_process_result->get_system_errors() !== []) {
            $this->changed_files_detector->invalidate_file($file->get_file_path());
        } elseif (!$configuration->is_dry_run() || !$file_process_result->get_file_diff() instanceof File_Diff) {
            $this->changed_files_detector->cache_file($file->get_file_path());
        }
        return $file_process_result;
    }
    private function resolve_system_error(Throwable $throwable, string $file_path): System_Error
    {
        $error_message = sprintf('System error: "%s"', $throwable->get_message()) . \PHP_EOL;
        if ($this->symfony_style->is_debug()) {
            $error_message .= \PHP_EOL . 'Stack trace:' . \PHP_EOL . $throwable->get_trace_as_string();
        } else {
            $error_message .= 'Run Rector with "--debug" option and post the report here: https://github.com/rectorphp/rector/issues/new';
        }
        if ($throwable instanceof Parser_Errors_Exception) {
            $throwable = new Parser_Errors($throwable);
        }
        return new System_Error($error_message, $file_path, $throwable->get_line());
    }
    /**
     * Inspired by @see https://github.com/phpstan/phpstan-src/blob/89af4e7db257750cdee5d4259ad312941b6b25e8/src/Analyser/Analyser.php#L134
     */
    private function configure_custom_error_handler(): void
    {
        $error_handler_callback = function (int $code, string $message, string $file, int $line): bool {
            if ((error_reporting() & $code) === 0) {
                // silence @ operator
                return \true;
            }
            // not relevant for us
            if (in_array($code, [\E_DEPRECATED, \E_WARNING], \true)) {
                return \true;
            }
            $this->system_errors[] = new System_Error($message, $file, $line);
            return \true;
        };
        set_error_handler($error_handler_callback);
    }
    private function restore_error_handler(): void
    {
        restore_error_handler();
    }
    /**
     * @param string[] $filePaths
     * @param callable(int $stepCount): void $postFileCallback
     */
    private function run_parallel(array $file_paths, Input_Interface $input, callable $post_file_callback): Process_Result
    {
        $schedule = $this->schedule_factory->create($this->cpu_core_count_provider->provide(), Simple_Parameter_Provider::provide_int_parameter(Option::PARALLEL_JOB_SIZE), Simple_Parameter_Provider::provide_int_parameter(Option::PARALLEL_MAX_NUMBER_OF_PROCESSES), $file_paths);
        $main_script = $this->resolve_called_rector_binary();
        if ($main_script === null) {
            throw new Parallel_Should_Not_Happen_Exception('[parallel] Main script was not found');
        }
        // mimics see https://github.com/phpstan/phpstan-src/commit/9124c66dcc55a222e21b1717ba5f60771f7dda92#diff-387b8f04e0db7a06678eb52ce0c0d0aff73e0d7d8fc5df834d0a5fbec198e5daR139
        return $this->parallel_file_processor->process($schedule, $main_script, $post_file_callback, $input);
    }
    /**
     * Path to called "rector" binary file, e.g. "vendor/bin/rector" returns "vendor/bin/rector" This is needed to re-call the
     * rector binary in sub-process in the same location.
     */
    private function resolve_called_rector_binary(): ?string
    {
        if (!isset($_SERVER[self::ARGV][0])) {
            return null;
        }
        $potential_rector_binary_path = $_SERVER[self::ARGV][0];
        if (!file_exists($potential_rector_binary_path)) {
            return null;
        }
        return $potential_rector_binary_path;
    }
}