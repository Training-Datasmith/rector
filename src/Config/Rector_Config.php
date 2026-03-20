<?php

declare (strict_types=1);
namespace Rector\Config;

use Override;
use Rector\Caching\Contract\Value_Object\Storage\Cache_Storage_Interface;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Configuration\Rector_Config_Builder;
use Rector\Contract\Dependency_Injection\Related_Config_Interface;
use Rector\Contract\Dependency_Injection\Resettable_Interface;
use Rector\Contract\Rector\Configurable_Rector_Interface;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Dependency_Injection\Laravel\Container_Memento;
use Rector\Enum\Config\Defaults;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector\Skipper\Skip_Criteria_Resolver\Skipped_Class_Resolver;
use Rector\Validation\Rector_Config_Validator;
use Rector\Value_Object\Configuration\Level_Overflow;
use Rector\Value_Object\Php_Version;
use Rector\Value_Object\Polyfill_Package;
use Rector_Prefix202603\Illuminate\Container\Container;
use Rector_Prefix202603\Symfony\Component\Console\Command\Command;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @api
 * @see \Rector\Tests\Config\RectorConfigTest
 */
final class Rector_Config extends Container
{
    /**
     * @var array<class-string<ConfigurableRectorInterface>, mixed[]>
     */
    private array $rule_configurations = [];
    /**
     * @var string[]
     */
    private array $autotag_interfaces = [Command::class, Resettable_Interface::class];
    private static ?bool $recreated = null;
    public static function configure(): Rector_Config_Builder
    {
        if (self::$recreated === null) {
            self::$recreated = \false;
        } elseif (self::$recreated === \false) {
            self::$recreated = \true;
        }
        Simple_Parameter_Provider::set_parameter(Option::IS_RECTORCONFIG_BUILDER_RECREATED, self::$recreated);
        return new Rector_Config_Builder();
    }
    /**
     * @param string[] $paths
     */
    public function paths(array $paths): void
    {
        Assert::all_string($paths);
        // ensure paths exist
        foreach ($paths as $path) {
            if (strpos($path, '*') !== \false) {
                continue;
            }
            Assert::file_exists($path);
        }
        Simple_Parameter_Provider::set_parameter(Option::PATHS, $paths);
    }
    /**
     * @param string[] $sets
     */
    public function sets(array $sets): void
    {
        Assert::all_string($sets);
        foreach ($sets as $set) {
            Assert::file_exists($set);
            $this->import($set);
        }
        // for cache invalidation in case of sets change
        Simple_Parameter_Provider::add_parameter(Option::REGISTERED_RECTOR_SETS, $sets);
    }
    public function disable_parallel(): void
    {
        Simple_Parameter_Provider::set_parameter(Option::PARALLEL, \false);
    }
    public function parallel(int $process_timeout = 120, int $max_number_of_process = Defaults::PARALLEL_MAX_NUMBER_OF_PROCESS, int $job_size = 16): void
    {
        Simple_Parameter_Provider::set_parameter(Option::PARALLEL, \true);
        Simple_Parameter_Provider::set_parameter(Option::PARALLEL_JOB_TIMEOUT_IN_SECONDS, $process_timeout);
        Simple_Parameter_Provider::set_parameter(Option::PARALLEL_MAX_NUMBER_OF_PROCESSES, $max_number_of_process);
        Simple_Parameter_Provider::set_parameter(Option::PARALLEL_JOB_SIZE, $job_size);
    }
    public function no_diffs(): void
    {
        Simple_Parameter_Provider::set_parameter(Option::NO_DIFFS, \true);
    }
    public function memory_limit(string $memory_limit): void
    {
        Simple_Parameter_Provider::set_parameter(Option::MEMORY_LIMIT, $memory_limit);
    }
    /**
     * @see https://getrector.com/documentation/ignoring-rules-or-paths
     * @param array<int|string, mixed> $skip
     */
    public function skip(array $skip): void
    {
        Rector_Config_Validator::ensure_rector_rules_exist($skip);
        Simple_Parameter_Provider::add_parameter(Option::SKIP, $skip);
    }
    public function remove_unused_imports(bool $remove_unused_imports = \true): void
    {
        Simple_Parameter_Provider::set_parameter(Option::REMOVE_UNUSED_IMPORTS, $remove_unused_imports);
    }
    public function import_names(bool $import_names = \true, bool $import_doc_block_names = \true): void
    {
        Simple_Parameter_Provider::set_parameter(Option::AUTO_IMPORT_NAMES, $import_names);
        Simple_Parameter_Provider::set_parameter(Option::AUTO_IMPORT_DOC_BLOCK_NAMES, $import_doc_block_names);
    }
    public function import_short_classes(bool $import_short_classes = \true): void
    {
        Simple_Parameter_Provider::set_parameter(Option::IMPORT_SHORT_CLASSES, $import_short_classes);
    }
    /**
     * Add PHPStan custom config to load extensions and custom configuration to Rector.
     */
    public function phpstan_config(string $file_path): void
    {
        Assert::file_exists($file_path);
        Simple_Parameter_Provider::add_parameter(Option::PHPSTAN_FOR_RECTOR_PATHS, [$file_path]);
    }
    /**
     * Add PHPStan custom configs to load extensions and custom configuration to Rector.
     *
     * @param string[] $filePaths
     */
    public function phpstan_configs(array $file_paths): void
    {
        Assert::all_string($file_paths);
        Assert::all_file_exists($file_paths);
        Simple_Parameter_Provider::add_parameter(Option::PHPSTAN_FOR_RECTOR_PATHS, $file_paths);
    }
    /**
     * @param class-string<ConfigurableRectorInterface> $rectorClass
     * @param mixed[] $configuration
     */
    public function rule_with_configuration(string $rector_class, array $configuration): void
    {
        Assert::class_exists($rector_class);
        Assert::is_a_of($rector_class, Rector_Interface::class);
        Assert::is_a_of($rector_class, Configurable_Rector_Interface::class);
        // store configuration to cache
        $this->rule_configurations[$rector_class] = array_merge($this->rule_configurations[$rector_class] ?? [], $configuration);
        $this->rule($rector_class);
        $this->after_resolving($rector_class, function (Configurable_Rector_Interface $configurable_rector) use ($rector_class): void {
            $rule_configuration = $this->rule_configurations[$rector_class];
            $configurable_rector->configure($rule_configuration);
        });
        // for cache invalidation in case of sets change
        Simple_Parameter_Provider::add_parameter(Option::REGISTERED_RECTOR_RULES, $rector_class);
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     */
    public function rule(string $rector_class): void
    {
        Assert::class_exists($rector_class);
        Assert::is_a_of($rector_class, Rector_Interface::class);
        $this->singleton($rector_class);
        $this->tag($rector_class, Rector_Interface::class);
        // for cache invalidation in case of change
        Simple_Parameter_Provider::add_parameter(Option::REGISTERED_RECTOR_RULES, $rector_class);
        if (is_a($rector_class, Related_Config_Interface::class, \true)) {
            $config_file = $rector_class::get_config_file();
            Assert::file($config_file, sprintf('The config path "%s" in "%s::getConfigFile()" could not be found', $config_file, $rector_class));
            $this->import($config_file);
        }
    }
    /**
     * @param class-string<Command> $commandClass
     */
    public function command(string $command_class): void
    {
        $this->singleton($command_class);
        $this->tag($command_class, Command::class);
    }
    public function import(string $file_path): void
    {
        /**
         * Only stop when filePath realpath is false and contains glob patterns
         * @see https://github.com/rectorphp/rector/issues/9156#issuecomment-2869130541
         */
        if (realpath($file_path) === \false && strpos($file_path, '*') !== \false) {
            throw new Should_Not_Happen_Exception('Matching file paths by using glob-patterns is no longer supported. Use specific file path instead.');
        }
        Assert::file_exists($file_path);
        $self = $this;
        $callable = require $file_path;
        Assert::is_callable($callable);
        /** @var callable(Container $container): void $callable */
        $callable($self);
    }
    /**
     * @param array<class-string<RectorInterface>> $rectorClasses
     */
    public function rules(array $rector_classes): void
    {
        Assert::all_string($rector_classes);
        Rector_Config_Validator::ensure_no_duplicated_classes($rector_classes);
        foreach ($rector_classes as $rector_class) {
            $this->rule($rector_class);
        }
    }
    /**
     * @param PhpVersion::* $phpVersion
     */
    public function php_version(int $php_version): void
    {
        Simple_Parameter_Provider::set_parameter(Option::PHP_VERSION_FEATURES, $php_version);
    }
    /**
     * @internal
     *
     * @api only for testing. It is parsed from composer.json "require" packages by default
     * @param array<PolyfillPackage::*> $polyfillPackages
     */
    public function polyfill_packages(array $polyfill_packages): void
    {
        Simple_Parameter_Provider::set_parameter(Option::POLYFILL_PACKAGES, $polyfill_packages);
    }
    /**
     * @param string[] $autoloadPaths
     */
    public function autoload_paths(array $autoload_paths): void
    {
        Assert::all_string($autoload_paths);
        Simple_Parameter_Provider::set_parameter(Option::AUTOLOAD_PATHS, $autoload_paths);
    }
    /**
     * @param string[] $bootstrapFiles
     */
    public function bootstrap_files(array $bootstrap_files): void
    {
        Assert::all_string($bootstrap_files);
        Simple_Parameter_Provider::set_parameter(Option::BOOTSTRAP_FILES, $bootstrap_files);
    }
    public function symfony_container_xml(string $file_path): void
    {
        Simple_Parameter_Provider::set_parameter(Option::SYMFONY_CONTAINER_XML_PATH_PARAMETER, $file_path);
    }
    public function symfony_container_php(string $file_path): void
    {
        Simple_Parameter_Provider::set_parameter(Option::SYMFONY_CONTAINER_PHP_PATH_PARAMETER, $file_path);
    }
    public function new_line_on_fluent_call(bool $enabled = \true): void
    {
        Simple_Parameter_Provider::set_parameter(Option::NEW_LINE_ON_FLUENT_CALL, $enabled);
    }
    public function treat_classes_as_final(bool $treat_classes_as_final = \true): void
    {
        Simple_Parameter_Provider::set_parameter(Option::TREAT_CLASSES_AS_FINAL, $treat_classes_as_final);
    }
    /**
     * @param string[] $extensions
     */
    public function file_extensions(array $extensions): void
    {
        Assert::all_string($extensions);
        Simple_Parameter_Provider::set_parameter(Option::FILE_EXTENSIONS, $extensions);
    }
    public function cache_directory(string $directory_path): void
    {
        // cache directory path is created via mkdir in CacheFactory
        // when not exists, so no need to validate $directoryPath is a directory
        Simple_Parameter_Provider::set_parameter(Option::CACHE_DIR, $directory_path);
    }
    public function container_cache_directory(string $directory_path): void
    {
        // container cache directory path must be a directory on the first place
        Assert::directory($directory_path);
        Simple_Parameter_Provider::set_parameter(Option::CONTAINER_CACHE_DIRECTORY, $directory_path);
    }
    /**
     * @param class-string<CacheStorageInterface> $cacheClass
     */
    public function cache_class(string $cache_class): void
    {
        Assert::is_a_of($cache_class, Cache_Storage_Interface::class);
        Simple_Parameter_Provider::set_parameter(Option::CACHE_CLASS, $cache_class);
    }
    /**
     * @see https://github.com/nikic/PHP-Parser/issues/723#issuecomment-712401963
     */
    public function indent(string $character, int $count): void
    {
        Simple_Parameter_Provider::set_parameter(Option::INDENT_CHAR, $character);
        Simple_Parameter_Provider::set_parameter(Option::INDENT_SIZE, $count);
    }
    /**
     * @internal
     * @api used only in tests
     */
    public function reset_rule_configurations(): void
    {
        $this->rule_configurations = [];
    }
    /**
     * Compiler passes-like method
     */
    public function boot(): void
    {
        $skipped_class_resolver = new Skipped_Class_Resolver();
        $skipped_elements = $skipped_class_resolver->resolve();
        foreach ($skipped_elements as $skipped_class => $path) {
            if ($path !== null) {
                continue;
            }
            // completely forget the Rector rule only when no path specified
            Container_Memento::forget_service($this, $skipped_class);
        }
    }
    /**
     * @internal Use to add tag on service registrations
     */
    public function autotag_interface(string $interface): void
    {
        $this->autotag_interfaces[] = $interface;
    }
    /**
     * @param string $abstract
     * @param mixed $concrete
     */
    #[Override]
    public function singleton($abstract, $concrete = null): void
    {
        parent::singleton($abstract, $concrete);
        foreach ($this->autotag_interfaces as $autotag_interface) {
            if (!is_a($abstract, $autotag_interface, \true)) {
                continue;
            }
            $this->tag($abstract, $autotag_interface);
        }
    }
    public function reporting_real_path(bool $absolute = \true): void
    {
        Simple_Parameter_Provider::set_parameter(Option::ABSOLUTE_FILE_PATH, $absolute);
    }
    public function editor_url(string $editor_url): void
    {
        Simple_Parameter_Provider::set_parameter(Option::EDITOR_URL, $editor_url);
    }
    /**
     * @internal Used only for bridge
     * @return array<class-string<ConfigurableRectorInterface>, mixed>
     */
    public function get_rule_configurations(): array
    {
        return $this->rule_configurations;
    }
    /**
     * @internal Used only for bridge
     * @return array<class-string<RectorInterface>>
     */
    public function get_main_rector_classes(): array
    {
        return $this->tags[Rector_Interface::class] ?? [];
    }
    /**
     * @internal used to report level overflows in configuration
     * @param LevelOverflow[] $levelOverflows
     */
    public function set_overflow_levels(array $level_overflows): void
    {
        Simple_Parameter_Provider::add_parameter(Option::LEVEL_OVERFLOWS, $level_overflows);
    }
}