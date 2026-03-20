<?php

declare (strict_types=1);
namespace Rector\Value_Object\Configuration;

final class Level_Overflow
{
    /**
     * @readonly
     */
    private string $configuration_name;
    /**
     * @readonly
     */
    private int $level;
    /**
     * @readonly
     */
    private int $rule_count;
    /**
     * @readonly
     */
    private string $suggested_ruleset;
    /**
     * @readonly
     */
    private string $suggested_set_list_constant;
    public function __construct(string $configuration_name, int $level, int $rule_count, string $suggested_ruleset, string $suggested_set_list_constant)
    {
        $this->configuration_name = $configuration_name;
        $this->level = $level;
        $this->rule_count = $rule_count;
        $this->suggested_ruleset = $suggested_ruleset;
        $this->suggested_set_list_constant = $suggested_set_list_constant;
    }
    public function get_configuration_name(): string
    {
        return $this->configuration_name;
    }
    public function get_level(): int
    {
        return $this->level;
    }
    public function get_rule_count(): int
    {
        return $this->rule_count;
    }
    public function get_suggested_ruleset(): string
    {
        return $this->suggested_ruleset;
    }
    public function get_suggested_set_list_constant(): string
    {
        return $this->suggested_set_list_constant;
    }
}