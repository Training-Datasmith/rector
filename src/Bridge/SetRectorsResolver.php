<?php

declare (strict_types=1);
namespace Rector\Bridge;

use Rector\Config\Rector_Config;
use Rector\Contract\Rector\Rector_Interface;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @api
 * Utils class to ease building bridges by 3rd-party tools
 *
 * @see \Rector\Tests\Bridge\SetRectorsResolverTest
 */
final class Set_Rectors_Resolver
{
    /**
     * @param string[] $configFilePaths
     * @return array<int, class-string<RectorInterface>|array<class-string<RectorInterface>, mixed[]>>
     */
    public function resolve_from_file_paths_including_configuration(array $config_file_paths): array
    {
        Assert::all_string($config_file_paths);
        Assert::all_file_exists($config_file_paths);
        $combined_rector_rules_with_configuration = [];
        foreach ($config_file_paths as $config_file_path) {
            $rector_rules_with_configuration = $this->resolve_from_file_path_including_configuration($config_file_path);
            $combined_rector_rules_with_configuration = array_merge($combined_rector_rules_with_configuration, $rector_rules_with_configuration);
        }
        return $combined_rector_rules_with_configuration;
    }
    /**
     * @return array<int, class-string<RectorInterface>|array<class-string<RectorInterface>, mixed[]>>
     */
    public function resolve_from_file_path_including_configuration(string $config_file_path): array
    {
        $rector_config = $this->load_rector_config_from_file_path($config_file_path);
        $rector_classes_with_optional_configuration = $rector_config->get_main_rector_classes();
        foreach ($rector_config->get_rule_configurations() as $rector_class => $configuration) {
            // remove from non-configurable, if added again with better config
            if (in_array($rector_class, $rector_classes_with_optional_configuration)) {
                $rector_rule_position = array_search($rector_class, $rector_classes_with_optional_configuration, \true);
                if (is_int($rector_rule_position)) {
                    unset($rector_classes_with_optional_configuration[$rector_rule_position]);
                }
            }
            $rector_classes_with_optional_configuration[] = [$rector_class => $configuration];
        }
        // sort keys
        return array_values($rector_classes_with_optional_configuration);
    }
    private function load_rector_config_from_file_path(string $config_file_path): Rector_Config
    {
        Assert::file_exists($config_file_path);
        $rector_config = new Rector_Config();
        /** @var callable $configCallable */
        $config_callable = require $config_file_path;
        $config_callable($rector_config);
        return $rector_config;
    }
}