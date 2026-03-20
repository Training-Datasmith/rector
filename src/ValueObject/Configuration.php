<?php

declare (strict_types=1);
namespace Rector\Value_Object;

use Rector\Changes_Reporting\Output\Console_Output_Formatter;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Value_Object\Configuration\Level_Overflow;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Configuration
{
    /**
     * @readonly
     */
    private bool $is_dry_run = \false;
    /**
     * @readonly
     */
    private bool $show_progress_bar = \true;
    /**
     * @readonly
     */
    private bool $should_clear_cache = \false;
    /**
     * @readonly
     */
    private string $output_format = Console_Output_Formatter::NAME;
    /**
     * @var string[]
     * @readonly
     */
    private array $file_extensions = ['php'];
    /**
     * @var string[]
     * @readonly
     */
    private array $paths = [];
    /**
     * @readonly
     */
    private bool $show_diffs = \true;
    /**
     * @readonly
     */
    private ?string $parallel_port;
    /**
     * @readonly
     */
    private ?string $parallel_identifier;
    /**
     * @readonly
     */
    private bool $is_parallel = \false;
    /**
     * @readonly
     */
    private ?string $memory_limit;
    /**
     * @readonly
     */
    private bool $is_debug = \false;
    /**
     * @readonly
     */
    private bool $reporting_with_real_path = \false;
    /**
     * @readonly
     */
    private ?string $only_rule = null;
    /**
     * @readonly
     */
    private ?string $only_suffix = null;
    /**
     * @var LevelOverflow[]
     * @readonly
     */
    private array $level_overflows = [];
    /**
     * @param string[] $fileExtensions
     * @param string[] $paths
     * @param LevelOverflow[] $levelOverflows
     */
    public function __construct(bool $is_dry_run = \false, bool $show_progress_bar = \true, bool $should_clear_cache = \false, string $output_format = Console_Output_Formatter::NAME, array $file_extensions = ['php'], array $paths = [], bool $show_diffs = \true, ?string $parallel_port = null, ?string $parallel_identifier = null, bool $is_parallel = \false, ?string $memory_limit = null, bool $is_debug = \false, bool $reporting_with_real_path = \false, ?string $only_rule = null, ?string $only_suffix = null, array $level_overflows = [])
    {
        $this->is_dry_run = $is_dry_run;
        $this->show_progress_bar = $show_progress_bar;
        $this->should_clear_cache = $should_clear_cache;
        $this->output_format = $output_format;
        $this->file_extensions = $file_extensions;
        $this->paths = $paths;
        $this->show_diffs = $show_diffs;
        $this->parallel_port = $parallel_port;
        $this->parallel_identifier = $parallel_identifier;
        $this->is_parallel = $is_parallel;
        $this->memory_limit = $memory_limit;
        $this->is_debug = $is_debug;
        $this->reporting_with_real_path = $reporting_with_real_path;
        $this->only_rule = $only_rule;
        $this->only_suffix = $only_suffix;
        $this->level_overflows = $level_overflows;
    }
    public function is_dry_run(): bool
    {
        return $this->is_dry_run;
    }
    public function should_show_progress_bar(): bool
    {
        return $this->show_progress_bar;
    }
    public function should_clear_cache(): bool
    {
        return $this->should_clear_cache;
    }
    /**
     * @return string[]
     */
    public function get_file_extensions(): array
    {
        Assert::not_empty($this->file_extensions);
        return $this->file_extensions;
    }
    public function get_only_rule(): ?string
    {
        return $this->only_rule;
    }
    /**
     * @return string[]
     */
    public function get_paths(): array
    {
        return $this->paths;
    }
    public function get_output_format(): string
    {
        return $this->output_format;
    }
    public function should_show_diffs(): bool
    {
        return $this->show_diffs;
    }
    public function get_parallel_port(): ?string
    {
        return $this->parallel_port;
    }
    public function get_parallel_identifier(): ?string
    {
        return $this->parallel_identifier;
    }
    public function is_parallel(): bool
    {
        return $this->is_parallel;
    }
    public function get_memory_limit(): ?string
    {
        return $this->memory_limit;
    }
    public function is_debug(): bool
    {
        return $this->is_debug;
    }
    public function is_reporting_with_real_path(): bool
    {
        return $this->reporting_with_real_path;
    }
    public function get_only_suffix(): ?string
    {
        return $this->only_suffix;
    }
    /**
     * @return LevelOverflow[]
     */
    public function get_level_overflows(): array
    {
        return $this->level_overflows;
    }
    /**
     * @return string[]
     */
    public function get_both_set_and_rules_duplicated_registrations(): array
    {
        $root_standalone_registered_rules = Simple_Parameter_Provider::provide_array_parameter(Option::ROOT_STANDALONE_REGISTERED_RULES);
        $set_registered_rules = Simple_Parameter_Provider::provide_array_parameter(Option::SET_REGISTERED_RULES);
        $rule_duplicated_registrations = array_intersect($root_standalone_registered_rules, $set_registered_rules);
        return array_unique($rule_duplicated_registrations);
    }
}