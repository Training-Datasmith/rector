<?php

declare (strict_types=1);
namespace Rector\Bootstrap;

use Rector\Rector_Installer\Generated_Config;
use ReflectionClass;
final class Extension_Config_Resolver
{
    /**
     * @api
     * @return string[]
     */
    public function provide(): array
    {
        $config_file_paths = [];
        if (!class_exists(\Rector\Rector_Installer\Generated_Config::class)) {
            return $config_file_paths;
        }
        $generated_config_reflection_class = new ReflectionClass(\Rector\Rector_Installer\Generated_Config::class);
        if ($generated_config_reflection_class->get_file_name() === \false) {
            return $config_file_paths;
        }
        $generated_config_directory = dirname($generated_config_reflection_class->get_file_name());
        foreach (Generated_Config::EXTENSIONS as $extension_config) {
            /** @var string[] $includedFiles */
            $included_files = $extension_config['extra']['includes'] ?? [];
            foreach ($included_files as $included_file) {
                $included_file_path = $this->resolve_include_file_path($extension_config, $generated_config_directory, $included_file);
                if ($included_file_path === null) {
                    /** @var string $installPath */
                    $install_path = $extension_config['install_path'];
                    $included_file_path = sprintf('%s/%s', $install_path, $included_file);
                }
                $config_file_paths[] = $included_file_path;
            }
        }
        return $config_file_paths;
    }
    /**
     * @param array<string, mixed> $extensionConfig
     */
    private function resolve_include_file_path(array $extension_config, string $generated_config_directory, string $included_file): ?string
    {
        if (!isset($extension_config['relative_install_path'])) {
            return null;
        }
        $included_file_path = sprintf('%s/%s/%s', $generated_config_directory, (string) $extension_config['relative_install_path'], $included_file);
        if (!file_exists($included_file_path)) {
            return null;
        }
        if (!is_readable($included_file_path)) {
            return null;
        }
        return $included_file_path;
    }
}