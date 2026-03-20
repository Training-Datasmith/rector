<?php

declare (strict_types=1);
namespace Rector\Configuration;

use Php_Parser\Node_Visitor;
use Rector\Bridge\Set_Provider_Collector;
use Rector\Bridge\Set_Rectors_Resolver;
use Rector\Caching\Contract\Value_Object\Storage\Cache_Storage_Interface;
use Rector\Composer\Installed_Package_Resolver;
use Rector\Config\Level\Code_Quality_Level;
use Rector\Config\Level\Coding_Style_Level;
use Rector\Config\Level\Dead_Code_Level;
use Rector\Config\Level\Type_Declaration_Docblocks_Level;
use Rector\Config\Level\Type_Declaration_Level;
use Rector\Config\Rector_Config;
use Rector\Config\Registered_Service;
use Rector\Configuration\Levels\Level_Rules_Resolver;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Console\Notifier;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Contract\Rector\Configurable_Rector_Interface;
use Rector\Contract\Rector\Rector_Interface;
use Rector\Doctrine\Set\Doctrine_Set_List;
use Rector\Enum\Config\Defaults;
use Rector\Exception\Configuration\Invalid_Configuration_Exception;
use Rector\Node_Type_Resolver\Php_Stan\Scope\Contract\Node_Visitor\Scope_Resolver_Node_Visitor_Interface;
use Rector\Php\Php_Version_Resolver\Composer_Json_Php_Version_Resolver;
use Rector\Php80\Rector\Class_\Annotation_To_Attribute_Rector;
use Rector\Php80\Value_Object\Annotation_To_Attribute;
use Rector\Php_Unit\Set\Php_Unit_Set_List;
use Rector\Set\Contract\Set_Provider_Interface;
use Rector\Set\Enum\Set_Group;
use Rector\Set\Set_Manager;
use Rector\Set\Value_Object\Downgrade_Level_Set_List;
use Rector\Set\Value_Object\Set_List;
use Rector\Symfony\Set\Symfony_Internal_Set_List;
use Rector\Symfony\Set\Symfony_Set_List;
use Rector\Value_Object\Configuration\Level_Overflow;
use Rector\Value_Object\Php_Version;
use Rector_Prefix202603\Symfony\Component\Console\Input\Argv_Input;
use Rector_Prefix202603\Symfony\Component\Console\Output\Console_Output;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
use Rector_Prefix202603\Symfony\Component\Finder\Finder;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @api
 */
final class Rector_Config_Builder
{
    /**
     * @var int
     */
    private const MAX_LEVEL_GAP = 10;
    /**
     * @var string[]
     */
    private array $paths = [];
    /**
     * @var string[]
     */
    private array $sets = [];
    /**
     * @var array<mixed>
     */
    private array $skip = [];
    /**
     * @var array<class-string<RectorInterface>>
     */
    private array $rules = [];
    /**
     * @var array<class-string<ConfigurableRectorInterface>, mixed[]>
     */
    private array $rules_with_configurations = [];
    /**
     * @var string[]
     */
    private array $file_extensions = [];
    /**
     * @var null|class-string<CacheStorageInterface>
     */
    private ?string $cache_class = null;
    private ?string $cache_directory = null;
    private ?string $container_cache_directory = null;
    private ?bool $parallel = null;
    private int $parallel_timeout_seconds = 120;
    private int $parallel_max_number_of_process = Defaults::PARALLEL_MAX_NUMBER_OF_PROCESS;
    private int $parallel_job_size = 16;
    private bool $import_names = \false;
    private bool $import_doc_block_names = \false;
    private bool $import_short_classes = \true;
    private bool $remove_unused_imports = \false;
    private bool $no_diffs = \false;
    private ?string $memory_limit = null;
    /**
     * @var string[]
     */
    private array $autoload_paths = [];
    /**
     * @var string[]
     */
    private array $bootstrap_files = [];
    private string $indent_char = ' ';
    private int $indent_size = 4;
    /**
     * @var string[]
     */
    private array $phpstan_configs = [];
    /**
     * @var null|PhpVersion::*
     */
    private ?int $php_version = null;
    private ?string $symfony_container_xml_file = null;
    private ?string $symfony_container_php_file = null;
    /**
     * To make sure type declarations set and level are not duplicated,
     * as both contain same rules
     */
    private ?bool $is_type_coverage_level_used = null;
    private ?bool $is_type_coverage_docblock_level_used = null;
    private ?bool $is_dead_code_level_used = null;
    private ?bool $is_code_quality_level_used = null;
    private ?bool $is_coding_style_level_used = null;
    private ?bool $is_fluent_new_line = null;
    private ?bool $is_treat_classes_as_final = null;
    /**
     * @var RegisteredService[]
     */
    private array $register_services = [];
    /**
     * @var array<SetGroup::*>
     */
    private array $set_groups = [];
    private ?bool $reporting_real_path = null;
    /**
     * @var string[]
     */
    private array $group_loaded_sets = [];
    private ?string $editor_url = null;
    private ?bool $is_with_php_sets_used = null;
    private ?bool $is_with_php_level_used = null;
    /**
     * @var array<class-string<SetProviderInterface>,bool>
     */
    private array $set_providers = [];
    /**
     * @var LevelOverflow[]
     */
    private array $level_overflows = [];
    public function __invoke(Rector_Config $rector_config): void
    {
        if ($this->set_groups !== [] || $this->set_providers !== []) {
            $set_provider_collector = new Set_Provider_Collector(array_map(\Closure::from_callable([$rector_config, 'make']), \array_keys($this->set_providers)));
            $set_manager = new Set_Manager($set_provider_collector, new Installed_Package_Resolver(getcwd()));
            $this->group_loaded_sets = $set_manager->match_by_set_groups($this->set_groups);
            Simple_Parameter_Provider::add_parameter(\Rector\Configuration\Option::COMPOSER_BASED_SETS, $this->group_loaded_sets);
        }
        // not to miss it by accident
        if ($this->is_with_php_sets_used === \true) {
            $this->sets[] = Set_List::PHP_POLYFILLS;
        }
        // merge sets together
        $this->sets = array_merge($this->sets, $this->group_loaded_sets);
        $unique_sets = array_unique($this->sets);
        if ($this->is_with_php_level_used && $this->is_with_php_sets_used) {
            throw new Invalid_Configuration_Exception(sprintf('Your config uses "withPhp*()" and "withPhpLevel()" methods at the same time.%sPick one of them to avoid rule conflicts.', \PHP_EOL));
        }
        if (in_array(Set_List::TYPE_DECLARATION, $unique_sets, \true) && $this->is_type_coverage_level_used === \true) {
            throw new Invalid_Configuration_Exception(sprintf('Your config already enables type declarations set.%sRemove "->withTypeCoverageLevel()" as it only duplicates it, or remove type declaration set.', \PHP_EOL));
        }
        if (in_array(Set_List::TYPE_DECLARATION_DOCBLOCKS, $unique_sets, \true) && $this->is_type_coverage_docblock_level_used === \true) {
            throw new Invalid_Configuration_Exception(sprintf('Your config already enables type declarations set.%sRemove "->withTypeCoverageDocblockLevel()" as it only duplicates it, or remove type declaration set.', \PHP_EOL));
        }
        if (in_array(Set_List::DEAD_CODE, $unique_sets, \true) && $this->is_dead_code_level_used === \true) {
            throw new Invalid_Configuration_Exception(sprintf('Your config already enables dead code set.%sRemove "->withDeadCodeLevel()" as it only duplicates it, or remove dead code set.', \PHP_EOL));
        }
        if (in_array(Set_List::CODE_QUALITY, $unique_sets, \true) && $this->is_code_quality_level_used === \true) {
            throw new Invalid_Configuration_Exception(sprintf('Your config already enables code quality set.%sRemove "->withCodeQualityLevel()" as it only duplicates it, or remove code quality set.', \PHP_EOL));
        }
        if (in_array(Set_List::CODING_STYLE, $unique_sets, \true) && $this->is_coding_style_level_used === \true) {
            throw new Invalid_Configuration_Exception(sprintf('Your config already enables coding style set.%sRemove "->withCodingStyleLevel()" as it only duplicates it, or remove coding style set.', \PHP_EOL));
        }
        if ($unique_sets !== []) {
            $rector_config->sets($unique_sets);
        }
        // log rules from sets and compare them with explicit rules
        $set_registered_rector_classes = $rector_config->get_main_rector_classes();
        Simple_Parameter_Provider::add_parameter(\Rector\Configuration\Option::SET_REGISTERED_RULES, $set_registered_rector_classes);
        if ($this->paths !== []) {
            $rector_config->paths($this->paths);
        }
        // must be in upper part, as these services might be used by rule registered bellow
        foreach ($this->register_services as $register_service) {
            $rector_config->singleton($register_service->get_class_name());
            if ($register_service->get_alias()) {
                $rector_config->alias($register_service->get_class_name(), $register_service->get_alias());
            }
            if ($register_service->get_tag()) {
                $rector_config->tag($register_service->get_class_name(), $register_service->get_tag());
            }
        }
        if ($this->skip !== []) {
            $rector_config->skip($this->skip);
        }
        if ($this->rules !== []) {
            $rector_config->rules($this->rules);
        }
        foreach ($this->rules_with_configurations as $rector_class => $configurations) {
            foreach ($configurations as $configuration) {
                $rector_config->rule_with_configuration($rector_class, $configuration);
            }
        }
        if ($this->file_extensions !== []) {
            $rector_config->file_extensions($this->file_extensions);
        }
        if ($this->cache_class !== null) {
            $rector_config->cache_class($this->cache_class);
        }
        if ($this->cache_directory !== null) {
            $rector_config->cache_directory($this->cache_directory);
        }
        if ($this->container_cache_directory !== null) {
            $rector_config->container_cache_directory($this->container_cache_directory);
        }
        if ($this->import_names || $this->import_doc_block_names) {
            $rector_config->import_names($this->import_names, $this->import_doc_block_names);
            $rector_config->import_short_classes($this->import_short_classes);
        }
        if ($this->remove_unused_imports) {
            $rector_config->remove_unused_imports($this->remove_unused_imports);
        }
        if ($this->no_diffs) {
            $rector_config->no_diffs();
        }
        if ($this->memory_limit !== null) {
            $rector_config->memory_limit($this->memory_limit);
        }
        if ($this->autoload_paths !== []) {
            $rector_config->autoload_paths($this->autoload_paths);
        }
        if ($this->bootstrap_files !== []) {
            $rector_config->bootstrap_files($this->bootstrap_files);
        }
        if ($this->indent_char !== ' ' || $this->indent_size !== 4) {
            $rector_config->indent($this->indent_char, $this->indent_size);
        }
        if ($this->phpstan_configs !== []) {
            $rector_config->phpstan_configs($this->phpstan_configs);
        }
        if ($this->php_version !== null) {
            $rector_config->php_version($this->php_version);
        }
        if ($this->parallel !== null) {
            if ($this->parallel) {
                $rector_config->parallel($this->parallel_timeout_seconds, $this->parallel_max_number_of_process, $this->parallel_job_size);
            } else {
                $rector_config->disable_parallel();
            }
        }
        if ($this->symfony_container_xml_file !== null) {
            $rector_config->symfony_container_xml($this->symfony_container_xml_file);
        }
        if ($this->symfony_container_php_file !== null) {
            $rector_config->symfony_container_php($this->symfony_container_php_file);
        }
        if ($this->is_fluent_new_line !== null) {
            $rector_config->new_line_on_fluent_call($this->is_fluent_new_line);
        }
        if ($this->is_treat_classes_as_final !== null) {
            $rector_config->treat_classes_as_final($this->is_treat_classes_as_final);
        }
        if ($this->reporting_real_path !== null) {
            $rector_config->reporting_real_path($this->reporting_real_path);
        }
        if ($this->editor_url !== null) {
            $rector_config->editor_url($this->editor_url);
        }
        if ($this->level_overflows !== []) {
            $rector_config->set_overflow_levels($this->level_overflows);
        }
    }
    /**
     * @param string[] $paths
     */
    public function with_paths(array $paths): self
    {
        $this->paths = $paths;
        return $this;
    }
    /**
     * @param array<mixed> $skip
     */
    public function with_skip(array $skip): self
    {
        $this->skip = array_merge($this->skip, $skip);
        return $this;
    }
    public function with_skip_path(string $skip_path): self
    {
        if (strpos($skip_path, '*') === \false) {
            Assert::file_exists($skip_path);
        }
        return $this->with_skip([$skip_path]);
    }
    /**
     * Include PHP files from the root directory (including hidden ones),
     * typically ecs.php, rector.php, .php-cs-fixer.dist.php etc.
     */
    public function with_root_files(): self
    {
        $root_php_files_finder = (new Finder())->files()->in(getcwd())->depth(0)->ignore_dot_files(\false)->ignore_vcs_ignored(\true)->name('*.php')->name('.*.php')->not_name('.phpstorm.meta.php');
        foreach ($root_php_files_finder as $root_php_file_finder) {
            $path = $root_php_file_finder->get_real_path();
            $this->paths[] = $path;
        }
        return $this;
    }
    /**
     * @param string[] $sets
     */
    public function with_sets(array $sets): self
    {
        $this->sets = array_merge($this->sets, $sets);
        return $this;
    }
    /**
     * Upgrade your annotations to attributes
     */
    public function with_attributes_sets(bool $symfony = \false, bool $doctrine = \false, bool $mongo_db = \false, bool $gedmo = \false, bool $phpunit = \false, bool $fos_rest = \false, bool $jms = \false, bool $sensiolabs = \false, bool $behat = \false, bool $all = \false, bool $symfony_route = \false, bool $symfony_validator = \false): self
    {
        // if nothing is passed, enable all as convention in other method
        if (func_get_args() === []) {
            $all = \true;
        }
        if ($symfony || $all) {
            $this->sets[] = Symfony_Set_List::ANNOTATIONS_TO_ATTRIBUTES;
        }
        // dx for more granular upgrade
        if ($symfony_route) {
            if ($symfony) {
                throw new Invalid_Configuration_Exception('$symfonyRoute is already included in $symfony. Use $symfony only');
            }
            $this->with_configured_rule(Annotation_To_Attribute_Rector::class, [new Annotation_To_Attribute('Symfony\Component\Routing\Annotation\Route')]);
        }
        if ($symfony_validator) {
            if ($symfony) {
                throw new Invalid_Configuration_Exception('$symfonyValidator is already included in $symfony. Use $symfony only');
            }
            $this->sets[] = Symfony_Set_List::SYMFONY_52_VALIDATOR_ATTRIBUTES;
        }
        if ($doctrine || $all) {
            $this->sets[] = Doctrine_Set_List::ANNOTATIONS_TO_ATTRIBUTES;
        }
        if ($mongo_db || $all) {
            $this->sets[] = Doctrine_Set_List::MONGODB__ANNOTATIONS_TO_ATTRIBUTES;
        }
        if ($gedmo || $all) {
            $this->sets[] = Doctrine_Set_List::GEDMO_ANNOTATIONS_TO_ATTRIBUTES;
        }
        if ($fos_rest || $all) {
            $this->sets[] = Symfony_Internal_Set_List::FOS_REST_ANNOTATIONS_TO_ATTRIBUTES;
        }
        if ($jms || $all) {
            $this->sets[] = Symfony_Internal_Set_List::JMS_ANNOTATIONS_TO_ATTRIBUTES;
        }
        if ($sensiolabs || $all) {
            $this->sets[] = Symfony_Internal_Set_List::SENSIOLABS_ANNOTATIONS_TO_ATTRIBUTES;
        }
        if ($phpunit || $all) {
            $this->sets[] = Php_Unit_Set_List::ANNOTATIONS_TO_ATTRIBUTES;
        }
        if ($behat || $all) {
            $this->sets[] = Set_List::BEHAT_ANNOTATIONS_TO_ATTRIBUTES;
        }
        return $this;
    }
    /**
     * What PHP sets should be applied? By default the same version
     * as composer.json has is used
     */
    public function with_php_sets(
        bool $php83 = \false,
        bool $php82 = \false,
        bool $php81 = \false,
        bool $php80 = \false,
        bool $php74 = \false,
        bool $php73 = \false,
        bool $php72 = \false,
        bool $php71 = \false,
        bool $php70 = \false,
        bool $php56 = \false,
        bool $php55 = \false,
        bool $php54 = \false,
        bool $php53 = \false,
        // place on later as BC break when used in php 7.x without named arg
        bool $php84 = \false,
        bool $php85 = \false
    ): self
    {
        if ($this->is_with_php_sets_used === \true) {
            throw new Invalid_Configuration_Exception(sprintf('Method "%s()" can be called only once. It always includes all previous sets UP TO the defined version.%sThe best practise is to call it once with no argument. That way it will pick up PHP version from composer.json and your project will always stay up to date.', __METHOD__, \PHP_EOL));
        }
        $this->is_with_php_sets_used = \true;
        $picked_arguments = array_filter(func_get_args());
        if ($picked_arguments !== []) {
            Notifier::error_with_php_sets_not_suitable_for_php74and_lower();
        }
        if (count($picked_arguments) > 1) {
            throw new Invalid_Configuration_Exception(sprintf('Pick only one version target in "withPhpSets()". All rules up to this version will be used.%sTo use your composer.json PHP version, keep arguments empty.', \PHP_EOL));
        }
        if ($picked_arguments === []) {
            $project_php_version = Composer_Json_Php_Version_Resolver::resolve_from_cwd_or_fail();
            $php_level_sets = \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version($project_php_version);
            $this->sets = array_merge($this->sets, $php_level_sets);
            return $this;
        }
        if ($php53) {
            $this->with_php53sets();
            return $this;
        }
        if ($php54) {
            $this->with_php54sets();
            return $this;
        }
        if ($php55) {
            $this->with_php55sets();
            return $this;
        }
        if ($php56) {
            $this->with_php56sets();
            return $this;
        }
        if ($php70) {
            $this->with_php70sets();
            return $this;
        }
        if ($php71) {
            $this->with_php71sets();
            return $this;
        }
        if ($php72) {
            $this->with_php72sets();
            return $this;
        }
        if ($php73) {
            $this->with_php73sets();
            return $this;
        }
        if ($php74) {
            $this->with_php74sets();
            return $this;
        }
        if ($php80) {
            $target_php_version = Php_Version::PHP_80;
        } elseif ($php81) {
            $target_php_version = Php_Version::PHP_81;
        } elseif ($php82) {
            $target_php_version = Php_Version::PHP_82;
        } elseif ($php83) {
            $target_php_version = Php_Version::PHP_83;
        } elseif ($php84) {
            $target_php_version = Php_Version::PHP_84;
        } elseif ($php85) {
            $target_php_version = Php_Version::PHP_85;
        } else {
            throw new Invalid_Configuration_Exception('Invalid PHP version set');
        }
        $php_level_sets = \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version($target_php_version);
        $this->sets = array_merge($this->sets, $php_level_sets);
        return $this;
    }
    /**
     * Following methods are suitable for PHP 7.4 and lower, before named args
     * Let's keep them without warning, in case Rector is run on both PHP 7.4 and PHP 8.0 in CI
     */
    public function with_php53sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_53));
        return $this;
    }
    public function with_php54sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_54));
        return $this;
    }
    public function with_php55sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_55));
        return $this;
    }
    public function with_php56sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_56));
        return $this;
    }
    public function with_php70sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_70));
        return $this;
    }
    public function with_php71sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_71));
        return $this;
    }
    public function with_php72sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_72));
        return $this;
    }
    public function with_php73sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_73));
        return $this;
    }
    public function with_php74sets(): self
    {
        $this->is_with_php_sets_used = \true;
        $this->sets = array_merge($this->sets, \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version(Php_Version::PHP_74));
        return $this;
    }
    // there is no withPhp80Sets() and above,
    // as we already use PHP 8.0 and should go with withPhpSets() instead
    public function with_prepared_sets(
        bool $dead_code = \false,
        bool $code_quality = \false,
        bool $coding_style = \false,
        bool $type_declarations = \false,
        bool $type_declaration_docblocks = \false,
        bool $privatization = \false,
        bool $naming = \false,
        bool $instance_of = \false,
        bool $early_return = \false,
        /** @deprecated */
        bool $strict_booleans = \false,
        bool $carbon = \false,
        bool $rector_preset = \false,
        bool $phpunit_code_quality = \false,
        bool $doctrine_code_quality = \false,
        bool $symfony_code_quality = \false,
        bool $symfony_configs = \false
    ): self
    {
        Notifier::notify_not_suitable_method_for_php74(__METHOD__);
        if ($strict_booleans) {
            $message = 'The "strictBooleans" set is deprecated as mostly risky and not practical. Remove it from withPreparedSets() method and use "codeQuality" and "codingStyle" sets instead. They already contain more granular and stable rules on same note.';
            $symfony_style = new Symfony_Style(new Argv_Input(), new Console_Output());
            $symfony_style->warning($message);
        }
        $set_map = [Set_List::DEAD_CODE => $dead_code, Set_List::CODE_QUALITY => $code_quality, Set_List::CODING_STYLE => $coding_style, Set_List::TYPE_DECLARATION => $type_declarations, Set_List::TYPE_DECLARATION_DOCBLOCKS => $type_declaration_docblocks, Set_List::PRIVATIZATION => $privatization, Set_List::NAMING => $naming, Set_List::INSTANCEOF => $instance_of, Set_List::EARLY_RETURN => $early_return, Set_List::CARBON => $carbon, Set_List::RECTOR_PRESET => $rector_preset, Php_Unit_Set_List::PHPUNIT_CODE_QUALITY => $phpunit_code_quality, Doctrine_Set_List::DOCTRINE_CODE_QUALITY => $doctrine_code_quality, Symfony_Set_List::SYMFONY_CODE_QUALITY => $symfony_code_quality, Symfony_Set_List::CONFIGS => $symfony_configs];
        foreach ($set_map as $set_path => $is_enabled) {
            if ($is_enabled) {
                $this->sets[] = $set_path;
            }
        }
        return $this;
    }
    public function with_composer_based(bool $twig = \false, bool $doctrine = \false, bool $phpunit = \false, bool $symfony = \false, bool $nette_utils = \false, bool $laravel = \false): self
    {
        $set_map = [Set_Group::TWIG => $twig, Set_Group::DOCTRINE => $doctrine, Set_Group::PHPUNIT => $phpunit, Set_Group::SYMFONY => $symfony, Set_Group::NETTE_UTILS => $nette_utils, Set_Group::LARAVEL => $laravel];
        foreach ($set_map as $set_path => $is_enabled) {
            if ($is_enabled) {
                $this->set_groups[] = $set_path;
            }
        }
        return $this;
    }
    /**
     * @param array<class-string<RectorInterface>> $rules
     */
    public function with_rules(array $rules): self
    {
        $this->rules = array_merge($this->rules, $rules);
        if (Simple_Parameter_Provider::provide_bool_parameter(\Rector\Configuration\Option::IS_RECTORCONFIG_BUILDER_RECREATED, \false) === \false) {
            // log all explicitly registered rules on root rector.php
            // we only check the non-configurable rules, as the configurable ones might override them
            $non_configurable_rules = array_filter($rules, fn(string $rule): bool => !is_a($rule, Configurable_Rector_Interface::class, \true));
            Simple_Parameter_Provider::add_parameter(\Rector\Configuration\Option::ROOT_STANDALONE_REGISTERED_RULES, $non_configurable_rules);
        }
        return $this;
    }
    /**
     * @param string[] $fileExtensions
     */
    public function with_file_extensions(array $file_extensions): self
    {
        $this->file_extensions = $file_extensions;
        return $this;
    }
    /**
     * @param class-string<CacheStorageInterface>|null $cacheClass
     */
    public function with_cache(?string $cache_directory = null, ?string $cache_class = null, ?string $container_cache_directory = null): self
    {
        $this->cache_directory = $cache_directory;
        $this->cache_class = $cache_class;
        $this->container_cache_directory = $container_cache_directory;
        return $this;
    }
    /**
     * @param class-string<ConfigurableRectorInterface> $rectorClass
     * @param mixed[] $configuration
     */
    public function with_configured_rule(string $rector_class, array $configuration): self
    {
        $this->rules_with_configurations[$rector_class][] = $configuration;
        return $this;
    }
    public function with_parallel(?int $timeout_seconds = null, ?int $max_number_of_process = null, ?int $job_size = null): self
    {
        $this->parallel = \true;
        if (is_int($timeout_seconds)) {
            $this->parallel_timeout_seconds = $timeout_seconds;
        }
        if (is_int($max_number_of_process)) {
            $this->parallel_max_number_of_process = $max_number_of_process;
        }
        if (is_int($job_size)) {
            $this->parallel_job_size = $job_size;
        }
        return $this;
    }
    public function without_parallel(): self
    {
        $this->parallel = \false;
        return $this;
    }
    public function with_import_names(bool $import_names = \true, bool $import_doc_block_names = \true, bool $import_short_classes = \true, bool $remove_unused_imports = \false): self
    {
        $this->import_names = $import_names;
        $this->import_doc_block_names = $import_doc_block_names;
        $this->import_short_classes = $import_short_classes;
        $this->remove_unused_imports = $remove_unused_imports;
        return $this;
    }
    public function with_no_diffs(): self
    {
        $this->no_diffs = \true;
        return $this;
    }
    public function with_memory_limit(string $memory_limit): self
    {
        $this->memory_limit = $memory_limit;
        return $this;
    }
    public function with_indent(string $indent_char = ' ', int $indent_size = 4): self
    {
        $this->indent_char = $indent_char;
        $this->indent_size = $indent_size;
        return $this;
    }
    /**
     * @param string[] $autoloadPaths
     */
    public function with_autoload_paths(array $autoload_paths): self
    {
        $this->autoload_paths = $autoload_paths;
        return $this;
    }
    /**
     * @param string[] $bootstrapFiles
     */
    public function with_bootstrap_files(array $bootstrap_files): self
    {
        $this->bootstrap_files = $bootstrap_files;
        return $this;
    }
    /**
     * @param string[] $phpstanConfigs
     */
    public function with_php_stan_configs(array $phpstan_configs): self
    {
        $this->phpstan_configs = $phpstan_configs;
        return $this;
    }
    /**
     * @param PhpVersion::* $phpVersion
     */
    public function with_php_version(int $php_version): self
    {
        $this->php_version = $php_version;
        return $this;
    }
    public function with_symfony_container_xml(string $symfony_container_xml_file): self
    {
        if (substr_compare($symfony_container_xml_file, '.xml', -strlen('.xml')) !== 0) {
            throw new Invalid_Configuration_Exception(sprintf('Provided dumped Symfony container must have "xml" suffix. "%s" given', $symfony_container_xml_file));
        }
        $this->symfony_container_xml_file = $symfony_container_xml_file;
        return $this;
    }
    public function with_symfony_container_php(string $symfony_container_php_file): self
    {
        if (substr_compare($symfony_container_php_file, '.php', -strlen('.php')) !== 0) {
            throw new Invalid_Configuration_Exception(sprintf('Provided dumped Symfony container must have "php" suffix. "%s" given', $symfony_container_php_file));
        }
        $this->symfony_container_php_file = $symfony_container_php_file;
        return $this;
    }
    /**
     * Raise your type coverage from the safest type rules
     * to more affecting ones, one level at a time
     */
    public function with_type_coverage_level(int $level): self
    {
        Assert::natural($level);
        $this->is_type_coverage_level_used = \true;
        $level_rules = Level_Rules_Resolver::resolve($level, Type_Declaration_Level::RULES, __METHOD__);
        // too high
        $level_rules_count = count($level_rules);
        if ($level_rules_count + self::MAX_LEVEL_GAP < $level) {
            $this->level_overflows[] = new Level_Overflow('withTypeCoverageLevel', $level, $level_rules_count, 'typeDeclarations', 'TYPE_DECLARATION');
        }
        $this->rules = array_merge($this->rules, $level_rules);
        return $this;
    }
    /**
     * Raise your type coverage docblock from the safest type rules
     * to more affecting ones, one level at a time
     */
    public function with_type_coverage_docblock_level(int $level): self
    {
        Assert::natural($level);
        $this->is_type_coverage_docblock_level_used = \true;
        $level_rules = Level_Rules_Resolver::resolve($level, Type_Declaration_Docblocks_Level::RULES, __METHOD__);
        // too high
        $level_rules_count = count($level_rules);
        if ($level_rules_count + self::MAX_LEVEL_GAP < $level) {
            $this->level_overflows[] = new Level_Overflow(__METHOD__, $level, $level_rules_count, 'typeDeclarationDocblocks', 'TYPE_DECLARATION_DOCBLOCKS');
        }
        $this->rules = array_merge($this->rules, $level_rules);
        return $this;
    }
    /**
     * Raise your dead-code coverage from the safest rules
     * to more affecting ones, one level at a time
     */
    public function with_dead_code_level(int $level): self
    {
        Assert::natural($level);
        $this->is_dead_code_level_used = \true;
        $level_rules = Level_Rules_Resolver::resolve($level, Dead_Code_Level::RULES, __METHOD__);
        // too high
        $level_rules_count = count($level_rules);
        if ($level_rules_count + self::MAX_LEVEL_GAP < $level) {
            $this->level_overflows[] = new Level_Overflow('withDeadCodeLevel', $level, $level_rules_count, 'deadCode', 'DEAD_CODE');
        }
        $this->rules = array_merge($this->rules, $level_rules);
        return $this;
    }
    /**
     * Raise your PHP level from, one level at a time
     */
    public function with_php_level(int $level): self
    {
        Assert::natural($level);
        $this->is_with_php_level_used = \true;
        $php_version = Composer_Json_Php_Version_Resolver::resolve_from_cwd_or_fail();
        $set_rectors_resolver = new Set_Rectors_Resolver();
        $set_file_paths = \Rector\Configuration\Php_Level_Set_Resolver::resolve_from_php_version($php_version);
        $rector_rules_with_configuration = $set_rectors_resolver->resolve_from_file_paths_including_configuration($set_file_paths);
        foreach ($rector_rules_with_configuration as $position => $rector_rule_with_configuration) {
            // add rules until level is reached
            if ($position > $level) {
                break;
            }
            if (is_string($rector_rule_with_configuration)) {
                $this->rules[] = $rector_rule_with_configuration;
            } elseif (is_array($rector_rule_with_configuration)) {
                foreach ($rector_rule_with_configuration as $rector_rule => $rector_rule_configuration) {
                    /** @var class-string<ConfigurableRectorInterface> $rectorRule */
                    $this->with_configured_rule($rector_rule, $rector_rule_configuration);
                }
            }
        }
        return $this;
    }
    /**
     * Raise your code quality from the safest rules
     * to more affecting ones, one level at a time
     */
    public function with_code_quality_level(int $level): self
    {
        Assert::natural($level);
        $this->is_code_quality_level_used = \true;
        $level_rules = Level_Rules_Resolver::resolve($level, Code_Quality_Level::RULES, __METHOD__);
        // too high
        $level_rules_count = count($level_rules);
        if ($level_rules_count + self::MAX_LEVEL_GAP < $level) {
            $this->level_overflows[] = new Level_Overflow('withCodeQualityLevel', $level, $level_rules_count, 'codeQuality', 'CODE_QUALITY');
        }
        $this->rules = array_merge($this->rules, $level_rules);
        foreach (Code_Quality_Level::RULES_WITH_CONFIGURATION as $rector_class => $configuration) {
            $this->rules_with_configurations[$rector_class][] = $configuration;
        }
        return $this;
    }
    /**
     * Raise your coding style from the safest rules
     * to more affecting ones, one level at a time
     */
    public function with_coding_style_level(int $level): self
    {
        Assert::natural($level);
        $this->is_coding_style_level_used = \true;
        $level_rules = Level_Rules_Resolver::resolve($level, Coding_Style_Level::RULES, __METHOD__);
        // too high
        $level_rules_count = count($level_rules);
        if ($level_rules_count + self::MAX_LEVEL_GAP < $level) {
            $this->level_overflows[] = new Level_Overflow('withCodingStyleLevel', $level, $level_rules_count, 'codingStyle', 'CODING_STYLE');
        }
        $this->rules = array_merge($this->rules, $level_rules);
        foreach (Coding_Style_Level::RULES_WITH_CONFIGURATION as $rector_class => $configuration) {
            $this->rules_with_configurations[$rector_class][] = $configuration;
        }
        return $this;
    }
    public function with_fluent_call_new_line(bool $is_fluent_new_line = \true): self
    {
        $this->is_fluent_new_line = $is_fluent_new_line;
        return $this;
    }
    public function with_treat_classes_as_final(bool $is_treat_classes_as_final = \true): self
    {
        $this->is_treat_classes_as_final = $is_treat_classes_as_final;
        return $this;
    }
    public function register_service(string $class_name, ?string $alias = null, ?string $tag = null): self
    {
        // BC layer since 2.2.9
        if ($tag === Scope_Resolver_Node_Visitor_Interface::class) {
            $tag = Decorating_Node_Visitor_Interface::class;
        }
        $this->register_services[] = new Registered_Service($class_name, $alias, $tag);
        return $this;
    }
    /**
     * DX helper
     * @see https://getrector.com/documentation/creating-a-node-visitor
     * @param class-string $decoratingNodeVisitorClass
     */
    public function register_decorating_node_visitor(string $decorating_node_visitor_class): self
    {
        Assert::is_a_of($decorating_node_visitor_class, Node_Visitor::class);
        $this->register_services[] = new Registered_Service($decorating_node_visitor_class, null, Decorating_Node_Visitor_Interface::class);
        return $this;
    }
    public function with_downgrade_sets(bool $php84 = \false, bool $php83 = \false, bool $php82 = \false, bool $php81 = \false, bool $php80 = \false, bool $php74 = \false, bool $php73 = \false, bool $php72 = \false, bool $php71 = \false): self
    {
        $picked_arguments = array_filter(func_get_args());
        if (count($picked_arguments) !== 1) {
            throw new Invalid_Configuration_Exception('Pick only one PHP version target in "withDowngradeSets()". All rules down to this version will be used.');
        }
        if ($php84) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_84;
        } elseif ($php83) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_83;
        } elseif ($php82) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_82;
        } elseif ($php81) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_81;
        } elseif ($php80) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_80;
        } elseif ($php74) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_74;
        } elseif ($php73) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_73;
        } elseif ($php72) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_72;
        } elseif ($php71) {
            $this->sets[] = Downgrade_Level_Set_List::DOWN_TO_PHP_71;
        }
        return $this;
    }
    public function with_real_path_reporting(bool $absolute_path = \true): self
    {
        $this->reporting_real_path = $absolute_path;
        return $this;
    }
    public function with_editor_url(string $editor_url): self
    {
        $this->editor_url = $editor_url;
        return $this;
    }
    /**
     * @param class-string<SetProviderInterface> ...$setProviders
     */
    public function with_set_providers(string ...$set_providers): self
    {
        foreach ($set_providers as $set_provider) {
            if (\array_key_exists($set_provider, $this->set_providers)) {
                continue;
            }
            if (!is_a($set_provider, Set_Provider_Interface::class, \true)) {
                throw new Invalid_Configuration_Exception(sprintf('Set provider "%s" must implement "%s"', $set_provider, Set_Provider_Interface::class));
            }
            $this->set_providers[$set_provider] = \true;
        }
        return $this;
    }
}