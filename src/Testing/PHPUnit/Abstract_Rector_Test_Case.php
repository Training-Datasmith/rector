<?php

declare (strict_types=1);
namespace Rector\Testing\Php_Unit;

use Iterator;
use Php_Unit\Framework\Expectation_Failed_Exception;
use Rector\Application\Application_File_Processor;
use Rector\Autoloading\Additional_Autoloader;
use Rector\Autoloading\Bootstrap_Files_Includer;
use Rector\Configuration\Configuration_Factory;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Contract\Dependency_Injection\Resettable_Interface;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Dependency_Injection\Laravel\Container_Memento;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator_Provider\Dynamic_Source_Locator_Provider;
use Rector\Php_Parser\Node_Traverser\Rector_Node_Traverser;
use Rector\Rector\Abstract_Rector;
use Rector\Testing\Contract\Rector_Test_Interface;
use Rector\Testing\Fixture\Fixture_File_Finder;
use Rector\Testing\Fixture\Fixture_File_Updater;
use Rector\Testing\Fixture\Fixture_Splitter;
use Rector\Testing\Php_Unit\Value_Object\Rector_Test_Result;
use Rector\Util\Reflection\Privates_Accessor;
use Rector_Prefix202603\Illuminate\Container\Rewindable_Generator;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Nette\Utils\Strings;
/**
 * @api used by public
 */
abstract class Abstract_Rector_Test_Case extends \Rector\Testing\Php_Unit\Abstract_Lazy_Test_Case implements Rector_Test_Interface
{
    private Dynamic_Source_Locator_Provider $dynamic_source_locator_provider;
    private Application_File_Processor $application_file_processor;
    private ?string $input_file_path = null;
    /**
     * @var array<string, true>
     */
    private static array $cache_by_rule_and_config = [];
    /**
     * Restore default parameters
     */
    public static function tear_down_after_class(): void
    {
        Simple_Parameter_Provider::set_parameter(Option::AUTO_IMPORT_NAMES, \false);
        Simple_Parameter_Provider::set_parameter(Option::AUTO_IMPORT_DOC_BLOCK_NAMES, \false);
        Simple_Parameter_Provider::set_parameter(Option::REMOVE_UNUSED_IMPORTS, \false);
        Simple_Parameter_Provider::set_parameter(Option::IMPORT_SHORT_CLASSES, \true);
        Simple_Parameter_Provider::set_parameter(Option::INDENT_CHAR, ' ');
        Simple_Parameter_Provider::set_parameter(Option::INDENT_SIZE, 4);
        Simple_Parameter_Provider::set_parameter(Option::POLYFILL_PACKAGES, []);
        Simple_Parameter_Provider::set_parameter(Option::NEW_LINE_ON_FLUENT_CALL, \false);
        Simple_Parameter_Provider::set_parameter(Option::TREAT_CLASSES_AS_FINAL, \false);
    }
    protected function set_up(): void
    {
        parent::set_up();
        $config_file = $this->provide_config_file_path();
        // cleanup all registered rectors, so you can use only the new ones
        $rector_config = self::get_container();
        // boot once for config + test case to avoid booting again and again for every test fixture
        $cache_key = sha1($config_file . static::class);
        if (!isset(self::$cache_by_rule_and_config[$cache_key])) {
            // reset
            /** @var RewindableGenerator<int, ResettableInterface> $resettables */
            $resettables = $rector_config->tagged(Resettable_Interface::class);
            foreach ($resettables as $resettable) {
                /** @var ResettableInterface $resettable */
                $resettable->reset();
            }
            $this->forget_rectors_rules();
            $rector_config->reset_rule_configurations();
            // this has to be always empty, so we can add new rules with their configuration
            $this->assert_empty($rector_config->tagged(Rector_Interface::class));
            $this->boot_from_config_files([$config_file]);
            $rectors_generator = $rector_config->tagged(Rector_Interface::class);
            $rectors = $rectors_generator instanceof Rewindable_Generator ? iterator_to_array($rectors_generator->getIterator()) : [];
            /** @var RectorNodeTraverser $rectorNodeTraverser */
            $rector_node_traverser = $rector_config->make(Rector_Node_Traverser::class);
            $rector_node_traverser->refresh_php_rectors($rectors);
            // store cache
            self::$cache_by_rule_and_config[$cache_key] = \true;
        }
        $this->application_file_processor = $this->make(Application_File_Processor::class);
        $this->dynamic_source_locator_provider = $this->make(Dynamic_Source_Locator_Provider::class);
        /** @var AdditionalAutoloader $additionalAutoloader */
        $additional_autoloader = $this->make(Additional_Autoloader::class);
        $additional_autoloader->autoload_paths();
        /** @var BootstrapFilesIncluder $bootstrapFilesIncluder */
        $bootstrap_files_includer = $this->make(Bootstrap_Files_Includer::class);
        $bootstrap_files_includer->include_bootstrap_files();
    }
    protected function tear_down(): void
    {
        // clear temporary file
        if (is_string($this->input_file_path)) {
            File_System::delete($this->input_file_path);
        }
    }
    protected static function yield_files_from_directory(string $directory, string $suffix = '*.php.inc'): Iterator
    {
        return Fixture_File_Finder::yield_directory($directory, $suffix);
    }
    protected function do_test_file(string $fixture_file_path, bool $include_fixture_directory_as_source = \false): void
    {
        // prepare input file contents and expected file output contents
        $fixture_file_contents = File_System::read($fixture_file_path);
        if (Fixture_Splitter::contains_split($fixture_file_contents)) {
            // changed content
            [$input_file_contents, $expected_file_contents] = Fixture_Splitter::split_fixture_file_contents($fixture_file_contents);
        } else {
            // no change
            $input_file_contents = $fixture_file_contents;
            $expected_file_contents = $fixture_file_contents;
        }
        $input_file_path = $this->create_input_file_path($fixture_file_path);
        // to remove later in tearDown()
        $this->input_file_path = $input_file_path;
        if ($fixture_file_path === $input_file_path) {
            throw new Should_Not_Happen_Exception('Fixture file and input file cannot be the same: ' . $fixture_file_path);
        }
        // write temp file
        File_System::write($input_file_path, $input_file_contents, null);
        $this->do_test_file_matches_expected_content($input_file_path, $input_file_contents, $expected_file_contents, $fixture_file_path, $include_fixture_directory_as_source);
    }
    protected function do_test_file_expecting_warning_about_rule_applied(string $fixture_file_path, string $expected_rule_applied): void
    {
        ob_start();
        $this->do_test_file($fixture_file_path);
        $content = ob_get_clean();
        $fixture_name = basename($fixture_file_path);
        $test_class = static::class;
        $this->assert_same(\PHP_EOL . 'WARNING: On fixture file "' . $fixture_name . '" for test "' . $test_class . '"' . \PHP_EOL . 'File not changed but some Rector rules applied:' . \PHP_EOL . ' * ' . $expected_rule_applied . \PHP_EOL, $content);
    }
    private function forget_rectors_rules(): void
    {
        $rector_config = self::get_container();
        // 1. forget tagged services
        Container_Memento::forget_tag($rector_config, Rector_Interface::class);
        // 2. remove after binding too, to avoid setting configuration over and over again
        $privates_accessor = new Privates_Accessor();
        $privates_accessor->property_closure($rector_config, 'afterResolvingCallbacks', static function (array $after_resolving_callbacks): array {
            foreach (array_keys($after_resolving_callbacks) as $key) {
                if ($key === Abstract_Rector::class) {
                    continue;
                }
                if (is_a($key, Rector_Interface::class, \true)) {
                    unset($after_resolving_callbacks[$key]);
                }
            }
            return $after_resolving_callbacks;
        });
    }
    private function do_test_file_matches_expected_content(string $original_file_path, string $input_file_contents, string $expected_file_contents, string $fixture_file_path, bool $include_fixture_directory_as_source): void
    {
        Simple_Parameter_Provider::set_parameter(Option::SOURCE, [$original_file_path]);
        // the file is now changed (if any rule matches)
        $rector_test_result = $this->process_file_path($original_file_path, $include_fixture_directory_as_source);
        $changed_contents = $rector_test_result->get_changed_contents();
        $fixture_filename = basename($fixture_file_path);
        $failure_message = sprintf('Failed on fixture file "%s"', $fixture_filename);
        $num_applied_rector_classes = count($rector_test_result->get_applied_rector_classes());
        // give more context about used rules in case of set testing
        $applied_rules_list = '';
        if ($num_applied_rector_classes > 0) {
            foreach ($rector_test_result->get_applied_rector_classes() as $applied_rector_class) {
                $applied_rules_list .= ' * ' . $applied_rector_class . \PHP_EOL;
            }
        }
        if ($num_applied_rector_classes > 1) {
            $failure_message .= \PHP_EOL . \PHP_EOL . 'Applied Rector rules:' . \PHP_EOL . $applied_rules_list;
        }
        try {
            $this->assert_same($expected_file_contents, $changed_contents, $failure_message);
        } catch (Expectation_Failed_Exception $exception) {
            Fixture_File_Updater::update_fixture_content($input_file_contents, $changed_contents, $fixture_file_path);
            // if not exact match, check the regex version (useful for generated hashes/uuids in the code)
            $this->assert_string_matches_format($expected_file_contents, $changed_contents, $failure_message);
        }
        if ($input_file_contents === $expected_file_contents && $num_applied_rector_classes > 0) {
            $failure_message = \PHP_EOL . sprintf('WARNING: On fixture file "%s" for test "%s"', $fixture_filename, static::class) . \PHP_EOL . 'File not changed but some Rector rules applied:' . \PHP_EOL . $applied_rules_list;
            echo $failure_message;
        }
    }
    private function process_file_path(string $file_path, bool $include_fixture_directory_as_source): Rector_Test_Result
    {
        if ($include_fixture_directory_as_source) {
            $fixture_directory = dirname($file_path);
            $this->dynamic_source_locator_provider->add_directories([$fixture_directory]);
        } else {
            $this->dynamic_source_locator_provider->set_file_path($file_path);
        }
        /** @var ConfigurationFactory $configurationFactory */
        $configuration_factory = $this->make(Configuration_Factory::class);
        $configuration = $configuration_factory->create_for_tests([$file_path]);
        $process_result = $this->application_file_processor->process_files([$file_path], $configuration);
        // return changed file contents
        $changed_file_contents = File_System::read($file_path);
        return new Rector_Test_Result($changed_file_contents, $process_result);
    }
    private function create_input_file_path(string $fixture_file_path): string
    {
        $input_file_directory = dirname($fixture_file_path);
        // remove ".inc" suffix
        if (substr_compare($fixture_file_path, '.inc', -strlen('.inc')) === 0) {
            $trimmed_fixture_file_path = Strings::substring($fixture_file_path, 0, -4);
        } else {
            $trimmed_fixture_file_path = $fixture_file_path;
        }
        $fixture_basename = pathinfo($trimmed_fixture_file_path, \PATHINFO_BASENAME);
        return $input_file_directory . '/' . $fixture_basename;
    }
}