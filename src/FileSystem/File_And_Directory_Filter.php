<?php

declare (strict_types=1);
namespace Rector\File_System;

/**
 * @see \Rector\Tests\FileSystem\FileAndDirectoryFilter\FileAndDirectoryFilterTest
 */
final class File_And_Directory_Filter
{
    /**
     * @param string[] $filesAndDirectories
     * @return string[]
     */
    public function filter_directories(array $files_and_directories): array
    {
        $directories = array_filter($files_and_directories, \Closure::from_callable('is_dir'));
        return array_values($directories);
    }
    /**
     * @param string[] $filesAndDirectories
     * @return string[]
     */
    public function filter_files(array $files_and_directories): array
    {
        $files = array_filter($files_and_directories, \Closure::from_callable('is_file'));
        return array_values($files);
    }
}