<?php

declare (strict_types=1);
namespace Rector\Value_Object\Bootstrap;

final class Bootstrap_Configs
{
    /**
     * @readonly
     */
    private ?string $main_config_file;
    /**
     * @var string[]
     * @readonly
     */
    private array $set_config_files;
    /**
     * @param string[] $setConfigFiles
     */
    public function __construct(?string $main_config_file, array $set_config_files)
    {
        $this->main_config_file = $main_config_file;
        $this->set_config_files = $set_config_files;
    }
    public function get_main_config_file(): ?string
    {
        return $this->main_config_file;
    }
    /**
     * @return string[]
     */
    public function get_config_files(): array
    {
        $config_files = [];
        if ($this->main_config_file !== null) {
            $config_files[] = $this->main_config_file;
        }
        return array_merge($config_files, $this->set_config_files);
    }
}