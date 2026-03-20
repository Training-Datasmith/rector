<?php

declare (strict_types=1);
namespace Rector\Bootstrap;

use Rector\Value_Object\Bootstrap\Bootstrap_Configs;
use Rector_Prefix202603\Symfony\Component\Console\Input\Argv_Input;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Rector_Configs_Resolver
{
    /**
     * @var string
     */
    public const DEFAULT_CONFIG_FILE = 'rector.php';
    /**
     * @var string
     */
    public const DEFAULT_DIST_CONFIG_FILE = 'rector.dist.php';
    public function provide(): Bootstrap_Configs
    {
        $argv_input = new Argv_Input();
        $main_config_file = $this->resolve_from_input_with_fallback($argv_input);
        return new Bootstrap_Configs($main_config_file, []);
    }
    private function resolve_from_input(Argv_Input $argv_input): ?string
    {
        $config_file = $this->get_option_value($argv_input, ['--config', '-c']);
        if ($config_file === null) {
            return null;
        }
        Assert::file_exists($config_file);
        return realpath($config_file);
    }
    private function resolve_from_input_with_fallback(Argv_Input $argv_input): ?string
    {
        $config_file = $this->resolve_from_input($argv_input);
        if ($config_file !== null) {
            return $config_file;
        }
        // Try rector.php first, then fall back to rector.dist.php
        $rector_config_file = $this->create_fallback_file_info_if_found(self::DEFAULT_CONFIG_FILE);
        if ($rector_config_file !== null) {
            return $rector_config_file;
        }
        return $this->create_fallback_file_info_if_found(self::DEFAULT_DIST_CONFIG_FILE);
    }
    private function create_fallback_file_info_if_found(string $fallback_file): ?string
    {
        $root_fallback_file = getcwd() . \DIRECTORY_SEPARATOR . $fallback_file;
        if (!is_file($root_fallback_file)) {
            return null;
        }
        return $root_fallback_file;
    }
    /**
     * @param string[] $optionNames
     */
    private function get_option_value(Argv_Input $argv_input, array $option_names): ?string
    {
        foreach ($option_names as $option_name) {
            if ($argv_input->has_parameter_option($option_name, \true)) {
                return $argv_input->get_parameter_option($option_name, null, \true);
            }
        }
        return null;
    }
}