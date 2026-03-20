<?php

declare (strict_types=1);
namespace Rector\Static_Reflection;

use Rector\File_System\File_And_Directory_Filter;
use Rector\File_System\Filesystem_Tweaker;
use Rector\Node_Type_Resolver\Reflection\Better_Reflection\Source_Locator_Provider\Dynamic_Source_Locator_Provider;
/**
 * @see https://phpstan.org/blog/zero-config-analysis-with-static-reflection
 * @see https://github.com/rectorphp/rector/issues/3490
 */
final class Dynamic_Source_Locator_Decorator
{
    /**
     * @readonly
     */
    private Dynamic_Source_Locator_Provider $dynamic_source_locator_provider;
    /**
     * @readonly
     */
    private File_And_Directory_Filter $file_and_directory_filter;
    /**
     * @readonly
     */
    private Filesystem_Tweaker $filesystem_tweaker;
    public function __construct(Dynamic_Source_Locator_Provider $dynamic_source_locator_provider, File_And_Directory_Filter $file_and_directory_filter, Filesystem_Tweaker $filesystem_tweaker)
    {
        $this->dynamic_source_locator_provider = $dynamic_source_locator_provider;
        $this->file_and_directory_filter = $file_and_directory_filter;
        $this->filesystem_tweaker = $filesystem_tweaker;
    }
    /**
     * @param string[] $paths
     * @return string[]
     */
    public function add_paths(array $paths): array
    {
        if ($paths === []) {
            return [];
        }
        $paths = $this->filesystem_tweaker->resolve_with_fnmatch($paths);
        $files = $this->file_and_directory_filter->filter_files($paths);
        $this->dynamic_source_locator_provider->add_files($files);
        $directories = $this->file_and_directory_filter->filter_directories($paths);
        $this->dynamic_source_locator_provider->add_directories($directories);
        return array_merge($files, $directories);
    }
    public function are_paths_empty(): bool
    {
        return $this->dynamic_source_locator_provider->are_paths_empty();
    }
}