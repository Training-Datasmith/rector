<?php

declare (strict_types=1);
namespace Rector\Console\Command;

use Rector\Application\Application_File_Processor;
use Rector\Autoloading\Additional_Autoloader;
use Rector\Caching\Detector\Changed_Files_Detector;
use Rector\Changes_Reporting\Output\Json_Output_Formatter;
use Rector\Configuration\Config_Initializer;
use Rector\Configuration\Configuration_Factory;
use Rector\Configuration\Configuration_Rule_Filter;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Console\Exit_Code;
use Rector\Console\Output\Output_Formatter_Collector;
use Rector\Console\Process_Configure_Decorator;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Reporting\Deprecated_Rules_Reporter;
use Rector\Reporting\Miss_Configuration_Reporter;
use Rector\Skipper\Skip_Criteria_Resolver\Skipped_Class_Resolver;
use Rector\Static_Reflection\Dynamic_Source_Locator_Decorator;
use Rector\Util\Memory_Limiter;
use Rector\Value_Object\Configuration;
use Rector\Value_Object\Configuration\Level_Overflow;
use Rector\Value_Object\Process_Result;
use Rector_Prefix202603\Symfony\Component\Console\Application;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Symfony\Component\Console\Input\Input_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Output\Output_Interface;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class Process_Command extends Command
{
    /**
     * @readonly
     */
    private Additional_Autoloader $additional_autoloader;
    /**
     * @readonly
     */
    private Changed_Files_Detector $changed_files_detector;
    /**
     * @readonly
     */
    private Config_Initializer $config_initializer;
    /**
     * @readonly
     */
    private Application_File_Processor $application_file_processor;
    /**
     * @readonly
     */
    private Dynamic_Source_Locator_Decorator $dynamic_source_locator_decorator;
    /**
     * @readonly
     */
    private Output_Formatter_Collector $output_formatter_collector;
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @readonly
     */
    private Memory_Limiter $memory_limiter;
    /**
     * @readonly
     */
    private Configuration_Factory $configuration_factory;
    /**
     * @readonly
     */
    private Deprecated_Rules_Reporter $deprecated_rules_reporter;
    /**
     * @readonly
     */
    private Miss_Configuration_Reporter $miss_configuration_reporter;
    /**
     * @readonly
     */
    private Configuration_Rule_Filter $configuration_rule_filter;
    /**
     * @readonly
     */
    private Skipped_Class_Resolver $skipped_class_resolver;
    public function __construct(Additional_Autoloader $additional_autoloader, Changed_Files_Detector $changed_files_detector, Config_Initializer $config_initializer, Application_File_Processor $application_file_processor, Dynamic_Source_Locator_Decorator $dynamic_source_locator_decorator, Output_Formatter_Collector $output_formatter_collector, Symfony_Style $symfony_style, Memory_Limiter $memory_limiter, Configuration_Factory $configuration_factory, Deprecated_Rules_Reporter $deprecated_rules_reporter, Miss_Configuration_Reporter $miss_configuration_reporter, Configuration_Rule_Filter $configuration_rule_filter, Skipped_Class_Resolver $skipped_class_resolver)
    {
        $this->additional_autoloader = $additional_autoloader;
        $this->changed_files_detector = $changed_files_detector;
        $this->config_initializer = $config_initializer;
        $this->application_file_processor = $application_file_processor;
        $this->dynamic_source_locator_decorator = $dynamic_source_locator_decorator;
        $this->output_formatter_collector = $output_formatter_collector;
        $this->symfony_style = $symfony_style;
        $this->memory_limiter = $memory_limiter;
        $this->configuration_factory = $configuration_factory;
        $this->deprecated_rules_reporter = $deprecated_rules_reporter;
        $this->miss_configuration_reporter = $miss_configuration_reporter;
        $this->configuration_rule_filter = $configuration_rule_filter;
        $this->skipped_class_resolver = $skipped_class_resolver;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_name('process');
        $this->set_description('Upgrades or refactors source code with provided Rector rules');
        $this->set_help(<<<'EOF'
        The <info>%command.name%</info> command will run Rector main feature:
        
          <info>vendor/bin/rector</info>
        
        To specify a folder or a file, you can run:
        
          <info>vendor/bin/rector src/Controller</info>
        
        You can also dry run to see the changes that Rector will make with the <comment>--dry-run</comment> option:
        
          <info>vendor/bin/rector src/Controller --dry-run</info>
        
        It's also possible to get debug via the <comment>--debug</comment> option:
        
          <info>vendor/bin/rector src/Controller --dry-run --debug</info>
        EOF);
        Process_Configure_Decorator::decorate($this);
        parent::configure();
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        // missing config? add it :)
        if (!$this->config_initializer->are_some_rectors_loaded()) {
            $this->config_initializer->create_config(getcwd());
            return self::SUCCESS;
        }
        $configuration = $this->configuration_factory->create_from_input($input);
        $this->memory_limiter->adjust($configuration);
        $this->configuration_rule_filter->set_configuration($configuration);
        // disable console output in case of json output formatter
        if ($configuration->get_output_format() === Json_Output_Formatter::NAME) {
            $this->symfony_style->set_verbosity(Output_Interface::VERBOSITY_QUIET);
        }
        $this->additional_autoloader->autoload_input($input);
        $paths = $configuration->get_paths();
        // 0. warn about too high levels
        foreach ($configuration->get_level_overflows() as $level_overflow) {
            $this->report_level_overflow($level_overflow);
        }
        // 0. warn about skipped rules that are deprecated
        if ($this->skipped_class_resolver->resolve_deprecated_skipped_classes() !== []) {
            $this->symfony_style->warning(sprintf('These rules are skipped, but are deprecated. Most likely you do not need to skip them anymore as not part of any set and remove them: %s* %s', "\n\n", implode(' * ', $this->skipped_class_resolver->resolve_deprecated_skipped_classes()) . "\n"));
        }
        // 1. warn about rules registered in both withRules() and sets to avoid bloated rector.php configs
        $set_and_rules_duplicated_registrations = $configuration->get_both_set_and_rules_duplicated_registrations();
        if ($set_and_rules_duplicated_registrations !== []) {
            $this->symfony_style->warning(sprintf('These rules are registered in both sets and "withRules()". Remove them from "withRules()" to avoid duplications: %s* %s', "\n\n", implode(' * ', $set_and_rules_duplicated_registrations) . "\n"));
        }
        // 2. add files and directories to static locator
        $this->dynamic_source_locator_decorator->add_paths($paths);
        if ($this->dynamic_source_locator_decorator->are_paths_empty()) {
            // read from rector.php, no paths definition needs withPaths() config
            if ($paths === []) {
                $this->symfony_style->error('Provide paths in Rector config. See ways: https://getrector.com/documentation/define-paths');
                return Exit_Code::FAILURE;
            }
            // read from cli paths arguments, eg: vendor/bin/rector process A B C which A, B, and C not exists
            $is_singular = count($paths) === 1;
            $this->symfony_style->error(sprintf('The following given path%s do%s not match any file%s or director%s: %s%s', $is_singular ? '' : 's', $is_singular ? 'es' : '', $is_singular ? '' : 's', $is_singular ? 'y' : 'ies', "\n\n" . ' - ', implode("\n" . ' - ', $paths)));
            return Exit_Code::FAILURE;
        }
        // autoload paths is register to DynamicSourceLocatorProvider,
        // so check after arePathsEmpty() above
        // check in no parallel since parallel will require register on its own process
        if (!$configuration->is_parallel()) {
            $this->additional_autoloader->autoload_paths();
        }
        // show debug info
        if ($configuration->is_debug()) {
            $this->report_loaded_composer_based_sets();
        }
        // MAIN PHASE
        // 2. run Rector
        $process_result = $this->application_file_processor->run($configuration, $input);
        // REPORTING PHASE
        // 3. reporting phaseRunning 2nd time with collectors data
        // report diffs and errors
        $output_format = $configuration->get_output_format();
        $output_formatter = $this->output_formatter_collector->get_by_name($output_format);
        $output_formatter->report($process_result, $configuration);
        // 4. Deprecations reporter
        $this->deprecated_rules_reporter->report_deprecated_rules();
        $this->deprecated_rules_reporter->report_deprecated_skipped_rules();
        $this->deprecated_rules_reporter->report_deprecated_node_types();
        $this->deprecated_rules_reporter->report_deprecated_rector_unsupported_methods();
        $this->miss_configuration_reporter->report_skipped_never_registered_rules();
        return $this->resolve_return_code($process_result, $configuration);
    }
    protected function initialize(Input_Interface $input, Output_Interface $output): void
    {
        $application = $this->get_application();
        if (!$application instanceof Application) {
            throw new Should_Not_Happen_Exception();
        }
        $option_debug = (bool) $input->get_option(Option::DEBUG);
        if ($option_debug) {
            $application->set_catch_exceptions(\false);
        }
        // clear cache
        $option_clear_cache = (bool) $input->get_option(Option::CLEAR_CACHE);
        if ($option_debug || $option_clear_cache) {
            $this->changed_files_detector->clear();
        }
    }
    /**
     * @return ExitCode::*
     */
    private function resolve_return_code(Process_Result $process_result, Configuration $configuration): int
    {
        // some system errors were found → fail
        if ($process_result->get_system_errors() !== []) {
            return Exit_Code::FAILURE;
        }
        // inverse error code for CI dry-run
        if (!$configuration->is_dry_run()) {
            return Exit_Code::SUCCESS;
        }
        if ($process_result->get_file_diffs() !== []) {
            return Exit_Code::CHANGED_CODE;
        }
        if ($process_result->get_total_changed() > 0) {
            return Exit_Code::CHANGED_CODE;
        }
        return Exit_Code::SUCCESS;
    }
    private function report_loaded_composer_based_sets(): void
    {
        if (!Simple_Parameter_Provider::has_parameter(Option::COMPOSER_BASED_SETS)) {
            return;
        }
        $composer_based_sets = Simple_Parameter_Provider::provide_array_parameter(Option::COMPOSER_BASED_SETS);
        if ($composer_based_sets === []) {
            return;
        }
        $this->symfony_style->writeln('[info] Sets loaded based on installed packages:');
        $this->symfony_style->listing($composer_based_sets);
    }
    private function report_level_overflow(Level_Overflow $level_overflow): void
    {
        $suggested_set_method = \PHP_VERSION_ID >= 80000 ? sprintf('->withPreparedSets(%s: true)', $level_overflow->get_suggested_ruleset()) : sprintf('->withSets(SetList::%s)', $level_overflow->get_suggested_set_list_constant());
        $this->symfony_style->warning(sprintf('The "->%s()" level contains only %d rules, but you set level to %d.%sYou are using the full set now! Time to switch to more efficient "%s".', $level_overflow->get_configuration_name(), $level_overflow->get_rule_count(), $level_overflow->get_level(), "\n", $suggested_set_method));
    }
}