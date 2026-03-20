<?php

declare (strict_types=1);
namespace Rector\File_System;

use Rector_Prefix202603\Symfony\Component\Finder\Finder;
use Rector_Prefix202603\Symfony\Component\Finder\Spl_File_Info;
/**
 * @see \Rector\Tests\FileSystem\InitFilePathsResolver\InitFilePathsResolverTest
 */
final class Init_File_Paths_Resolver
{
    /**
     * @see https://regex101.com/r/XkQ6Pe/1
     * @var string
     */
    private const DO_NOT_INCLUDE_PATHS_REGEX = '#(vendor|var|stubs|temp|templates|tmp|e2e|bin|build|Migrations|data(?:base)?|storage|migrations|writable|node_modules)#';
    /**
     * @return string[]
     */
    public function resolve(string $project_directory): array
    {
        $root_directory_finder = Finder::create()->directories()->depth(0)->not_path(self::DO_NOT_INCLUDE_PATHS_REGEX)->in($project_directory)->sort_by_name();
        /** @var SplFileInfo[] $rootDirectoryFileInfos */
        $root_directory_file_infos = iterator_to_array($root_directory_finder);
        $project_directories = [];
        foreach ($root_directory_file_infos as $root_directory_file_info) {
            if (!$this->has_directory_file_info_php_files($root_directory_file_info)) {
                continue;
            }
            $project_directories[] = $root_directory_file_info->get_relative_pathname();
        }
        return $project_directories;
    }
    private function has_directory_file_info_php_files(Spl_File_Info $root_directory_file_info): bool
    {
        // is directory with PHP files?
        return Finder::create()->files()->in($root_directory_file_info->get_pathname())->name('*.php')->has_results();
    }
}