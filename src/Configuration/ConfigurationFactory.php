<?php

declare (strict_types=1);
namespace Rector\Configuration;

use Rector\Changes_Reporting\Output\Console_Output_Formatter;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Value_Object\Configuration;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
/**
 * @see \Rector\Tests\Configuration\ConfigurationFactoryTest
 */
final class Configuration_Factory
{
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @readonly
     */
    private \Rector\Configuration\Only_Rule_Resolver $only_rule_resolver;
    public function __construct(Symfony_Style $symfony_style, \Rector\Configuration\Only_Rule_Resolver $only_rule_resolver)
    {
        $this->symfony_style = $symfony_style;
        $this->only_rule_resolver = $only_rule_resolver;
    }
    /**
     * @api used in tests
     * @param string[] $paths
     */
    public function create_for_tests(array $paths): Configuration
    {
        $file_extensions = Simple_Parameter_Provider::provide_array_parameter(\Rector\Configuration\Option::FILE_EXTENSIONS);
        return new Configuration(\false, \true, \false, Console_Output_Formatter::NAME, $file_extensions, $paths, \true, null, null, \false, null, \false, \false);
    }
    /**
     * Needs to run in the start of the life cycle, since the rest of workflow uses it.
     */
    public function create_from_input(Input_Interface $input): Configuration
    {
        $is_dry_run = (bool) $input->get_option(\Rector\Configuration\Option::DRY_RUN);
        $should_clear_cache = (bool) $input->get_option(\Rector\Configuration\Option::CLEAR_CACHE);
        $output_format = (string) $input->get_option(\Rector\Configuration\Option::OUTPUT_FORMAT);
        $show_progress_bar = $this->should_show_progress_bar($input, $output_format);
        $show_diffs = $this->should_show_diffs($input);
        $paths = $this->resolve_paths($input);
        $file_extensions = Simple_Parameter_Provider::provide_array_parameter(\Rector\Configuration\Option::FILE_EXTENSIONS);
        // filter rule and path
        $only_rule = $input->get_option(\Rector\Configuration\Option::ONLY);
        if ($only_rule !== null) {
            $only_rule = $this->only_rule_resolver->resolve($only_rule);
        }
        $only_suffix = $input->get_option(\Rector\Configuration\Option::ONLY_SUFFIX);
        $is_parallel = Simple_Parameter_Provider::provide_bool_parameter(\Rector\Configuration\Option::PARALLEL);
        $parallel_port = (string) $input->get_option(\Rector\Configuration\Option::PARALLEL_PORT);
        $parallel_identifier = (string) $input->get_option(\Rector\Configuration\Option::PARALLEL_IDENTIFIER);
        $is_debug = (bool) $input->get_option(\Rector\Configuration\Option::DEBUG);
        // using debug disables parallel, so emitting exception is straightforward and easier to debug
        if ($is_debug) {
            $is_parallel = \false;
        }
        $memory_limit = $this->resolve_memory_limit($input);
        $is_reporting_with_real_path = Simple_Parameter_Provider::provide_bool_parameter(\Rector\Configuration\Option::ABSOLUTE_FILE_PATH);
        $level_overflows = Simple_Parameter_Provider::provide_array_parameter(\Rector\Configuration\Option::LEVEL_OVERFLOWS);
        return new Configuration($is_dry_run, $show_progress_bar, $should_clear_cache, $output_format, $file_extensions, $paths, $show_diffs, $parallel_port, $parallel_identifier, $is_parallel, $memory_limit, $is_debug, $is_reporting_with_real_path, $only_rule, $only_suffix, $level_overflows);
    }
    private function should_show_progress_bar(Input_Interface $input, string $output_format): bool
    {
        $no_progress_bar = (bool) $input->get_option(\Rector\Configuration\Option::NO_PROGRESS_BAR);
        if ($no_progress_bar) {
            return \false;
        }
        if ($this->symfony_style->is_verbose()) {
            return \false;
        }
        return $output_format === Console_Output_Formatter::NAME;
    }
    private function should_show_diffs(Input_Interface $input): bool
    {
        $no_diffs = (bool) $input->get_option(\Rector\Configuration\Option::NO_DIFFS);
        if ($no_diffs) {
            return \false;
        }
        // fallback to parameter
        return !Simple_Parameter_Provider::provide_bool_parameter(\Rector\Configuration\Option::NO_DIFFS, \false);
    }
    /**
     * @return string[]|mixed[]
     */
    private function resolve_paths(Input_Interface $input): array
    {
        $command_line_paths = (array) $input->get_argument(\Rector\Configuration\Option::SOURCE);
        // give priority to command line
        if ($command_line_paths !== []) {
            $this->set_files_without_extension_parameter($command_line_paths);
            return $command_line_paths;
        }
        // fallback to parameter
        $config_paths = Simple_Parameter_Provider::provide_array_parameter(\Rector\Configuration\Option::PATHS);
        $this->set_files_without_extension_parameter($config_paths);
        return $config_paths;
    }
    /**
     * @param string[] $paths
     */
    private function set_files_without_extension_parameter(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path) && pathinfo($path, \PATHINFO_EXTENSION) === '') {
                $path = realpath($path);
                if ($path === \false) {
                    continue;
                }
                Simple_Parameter_Provider::add_parameter(\Rector\Configuration\Option::FILES_WITHOUT_EXTENSION, $path);
            }
        }
    }
    private function resolve_memory_limit(Input_Interface $input): ?string
    {
        $memory_limit = $input->get_option(\Rector\Configuration\Option::MEMORY_LIMIT);
        if ($memory_limit !== null) {
            return (string) $memory_limit;
        }
        if (!Simple_Parameter_Provider::has_parameter(\Rector\Configuration\Option::MEMORY_LIMIT)) {
            return null;
        }
        return Simple_Parameter_Provider::provide_string_parameter(\Rector\Configuration\Option::MEMORY_LIMIT);
    }
}