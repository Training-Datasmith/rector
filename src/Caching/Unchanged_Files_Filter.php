<?php

declare (strict_types=1);
namespace Rector\Caching;

use Rector\Caching\Detector\Changed_Files_Detector;
final class Unchanged_Files_Filter
{
    /**
     * @readonly
     */
    private Changed_Files_Detector $changed_files_detector;
    public function __construct(Changed_Files_Detector $changed_files_detector)
    {
        $this->changed_files_detector = $changed_files_detector;
    }
    /**
     * @param string[] $filePaths
     * @return string[]
     */
    public function filter_file_paths(array $file_paths): array
    {
        $changed_file_infos = [];
        $file_paths = array_unique($file_paths);
        foreach ($file_paths as $file_path) {
            if (!$this->changed_files_detector->has_file_changed($file_path)) {
                continue;
            }
            $changed_file_infos[] = $file_path;
            $this->changed_files_detector->invalidate_file($file_path);
        }
        return $changed_file_infos;
    }
}