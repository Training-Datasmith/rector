<?php

declare (strict_types=1);
namespace Rector\Configuration;

use Rector\Bootstrap\Rector_Configs_Resolver;
use Rector\Contract\Rector\Rector_Interface;
use Rector\File_System\Init_File_Paths_Resolver;
use Rector\Post_Rector\Contract\Rector\Post_Rector_Interface;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Symfony\Component\Console\Style\Symfony_Style;
final class Config_Initializer
{
    /**
     * @var RectorInterface[]
     * @readonly
     */
    private array $rectors;
    /**
     * @readonly
     */
    private Init_File_Paths_Resolver $init_file_paths_resolver;
    /**
     * @readonly
     */
    private Symfony_Style $symfony_style;
    /**
     * @param RectorInterface[] $rectors
     */
    public function __construct(array $rectors, Init_File_Paths_Resolver $init_file_paths_resolver, Symfony_Style $symfony_style)
    {
        $this->rectors = $rectors;
        $this->init_file_paths_resolver = $init_file_paths_resolver;
        $this->symfony_style = $symfony_style;
    }
    public function create_config(string $project_directory): void
    {
        $common_rector_config_path = $project_directory . '/' . Rector_Configs_Resolver::DEFAULT_CONFIG_FILE;
        $dist_rector_config_path = $project_directory . '/' . Rector_Configs_Resolver::DEFAULT_DIST_CONFIG_FILE;
        if (file_exists($common_rector_config_path)) {
            $this->symfony_style->warning('Register rules or sets in your "' . Rector_Configs_Resolver::DEFAULT_CONFIG_FILE . '" config');
            return;
        }
        if (file_exists($dist_rector_config_path)) {
            $this->symfony_style->warning('Register rules or sets in your "' . Rector_Configs_Resolver::DEFAULT_DIST_CONFIG_FILE . '" config');
            return;
        }
        $response = $this->symfony_style->ask('No "' . Rector_Configs_Resolver::DEFAULT_CONFIG_FILE . '" config found. Should we generate it for you?', 'yes');
        // be tolerant about input
        if (!in_array($response, ['yes', 'YES', 'y', 'Y'], \true)) {
            // okay, nothing we can do
            return;
        }
        $config_contents = File_System::read(__DIR__ . '/../../templates/rector.php.dist');
        $config_contents = $this->replace_paths_contents($config_contents, $project_directory);
        File_System::write($common_rector_config_path, $config_contents, null);
        $this->symfony_style->success('The config is added now. Re-run command to make Rector do the work!');
    }
    public function are_some_rectors_loaded(): bool
    {
        $active_rectors = $this->filter_active_rectors($this->rectors);
        return $active_rectors !== [];
    }
    /**
     * @param RectorInterface[] $rectors
     * @return RectorInterface[]
     */
    private function filter_active_rectors(array $rectors): array
    {
        return array_filter($rectors, static fn(Rector_Interface $rector): bool => !$rector instanceof Post_Rector_Interface);
    }
    private function replace_paths_contents(string $rector_php_template_contents, string $project_directory): string
    {
        $project_php_directories = $this->init_file_paths_resolver->resolve($project_directory);
        // fallback to default 'src' in case of empty one
        if ($project_php_directories === []) {
            $project_php_directories[] = 'src';
        }
        $project_php_directories_contents = '';
        foreach ($project_php_directories as $project_php_directory) {
            $project_php_directories_contents .= "        __DIR__ . '/" . $project_php_directory . "'," . \PHP_EOL;
        }
        $project_php_directories_contents = rtrim($project_php_directories_contents);
        return str_replace('__PATHS__', $project_php_directories_contents, $rector_php_template_contents);
    }
}