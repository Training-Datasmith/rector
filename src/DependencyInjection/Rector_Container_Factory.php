<?php

declare (strict_types=1);
namespace Rector\Dependency_Injection;

use Rector\Autoloading\Bootstrap_Files_Includer;
use Rector\Caching\Detector\Changed_Files_Detector;
use Rector\Config\Rector_Config;
use Rector\Value_Object\Bootstrap\Bootstrap_Configs;
final class Rector_Container_Factory
{
    public function create_from_bootstrap_configs(Bootstrap_Configs $bootstrap_configs): Rector_Config
    {
        $rector_config = $this->create_from_configs($bootstrap_configs->get_config_files());
        $main_config_file = $bootstrap_configs->get_main_config_file();
        if ($main_config_file !== null) {
            /** @var ChangedFilesDetector $changedFilesDetector */
            $changed_files_detector = $rector_config->make(Changed_Files_Detector::class);
            $changed_files_detector->set_first_resolved_config_file_info($main_config_file);
        }
        /** @var BootstrapFilesIncluder $bootstrapFilesIncluder */
        $bootstrap_files_includer = $rector_config->get(Bootstrap_Files_Includer::class);
        $bootstrap_files_includer->include_bootstrap_files();
        return $rector_config;
    }
    /**
     * @param string[] $configFiles
     */
    private function create_from_configs(array $config_files): Rector_Config
    {
        $lazy_container_factory = new \Rector\Dependency_Injection\Lazy_Container_Factory();
        $rector_config = $lazy_container_factory->create();
        foreach ($config_files as $config_file) {
            $rector_config->import($config_file);
        }
        $rector_config->boot();
        return $rector_config;
    }
}