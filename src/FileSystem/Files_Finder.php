<?php

declare (strict_types=1);
namespace Rector\File_System;

use Rector\Caching\Detector\Changed_Files_Detector;
use Rector\Caching\Unchanged_Files_Filter;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Skipper\Skipper\Path_Skipper;
use Rector\Value_Object\Configuration;
use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Symfony\Component\Finder\Finder;
/**
 * @see \Rector\Tests\FileSystem\FilesFinder\FilesFinderTest
 */
final class Files_Finder
{
    /**
     * @readonly
     */
    private \Rector\File_System\Filesystem_Tweaker $filesystem_tweaker;
    /**
     * @readonly
     */
    private Unchanged_Files_Filter $unchanged_files_filter;
    /**
     * @readonly
     */
    private \Rector\File_System\File_And_Directory_Filter $file_and_directory_filter;
    /**
     * @readonly
     */
    private Path_Skipper $path_skipper;
    /**
     * @readonly
     */
    private \Rector\File_System\File_Path_Helper $file_path_helper;
    /**
     * @readonly
     */
    private Changed_Files_Detector $changed_files_detector;
    public function __construct(\Rector\File_System\Filesystem_Tweaker $filesystem_tweaker, Unchanged_Files_Filter $unchanged_files_filter, \Rector\File_System\File_And_Directory_Filter $file_and_directory_filter, Path_Skipper $path_skipper, \Rector\File_System\File_Path_Helper $file_path_helper, Changed_Files_Detector $changed_files_detector)
    {
        $this->filesystem_tweaker = $filesystem_tweaker;
        $this->unchanged_files_filter = $unchanged_files_filter;
        $this->file_and_directory_filter = $file_and_directory_filter;
        $this->path_skipper = $path_skipper;
        $this->file_path_helper = $file_path_helper;
        $this->changed_files_detector = $changed_files_detector;
    }
    /**
     * @param string[] $source
     * @param string[] $suffixes
     * @return string[]
     */
    public function find_in_directories_and_files(array $source, array $suffixes = [], bool $sort_by_name = \true, ?string $only_suffix = null): array
    {
        $files_and_directories = $this->filesystem_tweaker->resolve_with_fnmatch($source);
        // filtering files in files collection
        $filtered_file_paths = $this->file_and_directory_filter->filter_files($files_and_directories);
        $filtered_file_paths = array_filter($filtered_file_paths, fn(string $file_path): bool => !$this->path_skipper->should_skip($file_path));
        // fallback append `.php` to be used for both $filteredFilePaths and $filteredFilePathsInDirectories
        $has_only_suffix = $only_suffix !== null && $only_suffix !== '';
        if ($has_only_suffix && substr_compare($only_suffix, '.php', -strlen('.php')) !== 0) {
            $only_suffix .= '.php';
        }
        // filter files by specific suffix
        if ($has_only_suffix) {
            /** @var string $onlySuffix */
            $file_with_suffix_filter = static fn(string $file_path): bool => substr_compare($file_path, $only_suffix, -strlen($only_suffix)) === 0;
        } elseif ($suffixes !== []) {
            $file_with_suffix_filter = static function (string $file_path) use ($suffixes): bool {
                $file_path_extension = pathinfo($file_path, \PATHINFO_EXTENSION);
                return in_array($file_path_extension, $suffixes, \true);
            };
        } else {
            $file_with_suffix_filter = fn(): bool => \true;
        }
        $filtered_file_paths = array_filter($filtered_file_paths, $file_with_suffix_filter ?? fn($value, $key): bool => !empty($value), $file_with_suffix_filter === null ? \ARRAY_FILTER_USE_BOTH : 0);
        // add file without extension after file extension filter
        $filtered_file_paths = array_merge($filtered_file_paths, Simple_Parameter_Provider::provide_array_parameter(Option::FILES_WITHOUT_EXTENSION));
        $filtered_file_paths = array_filter($filtered_file_paths, function (string $file): bool {
            if ($this->is_start_with_short_php_tag(File_System::read($file))) {
                Simple_Parameter_Provider::add_parameter(Option::SKIPPED_START_WITH_SHORT_OPEN_TAG_FILES, $this->file_path_helper->relative_path($file));
                return \false;
            }
            return \true;
        });
        // filtering files in directories collection
        $directories = $this->file_and_directory_filter->filter_directories($files_and_directories);
        $filtered_file_paths_in_directories = $this->find_in_directories($directories, $suffixes, $has_only_suffix, $only_suffix, $sort_by_name);
        $file_paths = array_merge($filtered_file_paths, $filtered_file_paths_in_directories);
        return $this->unchanged_files_filter->filter_file_paths($file_paths);
    }
    /**
     * @param string[] $paths
     * @return string[]
     */
    public function find_files_in_paths(array $paths, Configuration $configuration): array
    {
        if ($configuration->should_clear_cache()) {
            $this->changed_files_detector->clear();
        }
        return $this->find_in_directories_and_files($paths, $configuration->get_file_extensions(), \true, $configuration->get_only_suffix());
    }
    /**
     * Exclude short "<?=" tags as lead to invalid changes
     */
    private function is_start_with_short_php_tag(string $file_content): bool
    {
        return strncmp(ltrim($file_content), '<?=', strlen('<?=')) === 0;
    }
    /**
     * @param string[] $directories
     * @param string[] $suffixes
     * @return string[]
     */
    private function find_in_directories(array $directories, array $suffixes, bool $has_only_suffix, ?string $only_suffix = null, bool $sort_by_name = \true): array
    {
        if ($directories === []) {
            return [];
        }
        $finder = Finder::create()->files()->size('> 0')->in($directories);
        // filter files by specific suffix
        if ($has_only_suffix) {
            $finder->name('*' . $only_suffix);
        } elseif ($suffixes !== []) {
            $suffixes_pattern = $this->normalize_suffixes_to_pattern($suffixes);
            $finder->name($suffixes_pattern);
        }
        if ($sort_by_name) {
            $finder->sort_by_name();
        }
        $file_paths = [];
        foreach ($finder as $file_info) {
            // getRealPath() function will return false when it checks broken symlinks.
            // So we should check if this file exists or we got broken symlink
            /** @var string|false $path */
            $path = $file_info->get_real_path();
            if ($path === \false) {
                continue;
            }
            if ($this->path_skipper->should_skip($path)) {
                continue;
            }
            if ($this->is_start_with_short_php_tag($file_info->get_contents())) {
                Simple_Parameter_Provider::add_parameter(Option::SKIPPED_START_WITH_SHORT_OPEN_TAG_FILES, $this->file_path_helper->relative_path($path));
                continue;
            }
            $file_paths[] = $path;
        }
        return $file_paths;
    }
    /**
     * @param string[] $suffixes
     */
    private function normalize_suffixes_to_pattern(array $suffixes): string
    {
        $suffixes_pattern = implode('|', $suffixes);
        return '#\.(' . $suffixes_pattern . ')$#';
    }
}