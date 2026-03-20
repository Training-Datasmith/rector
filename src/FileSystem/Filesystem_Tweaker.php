<?php

declare (strict_types=1);
namespace Rector\File_System;

final class Filesystem_Tweaker
{
    /**
     * This will turn paths like "src/Symfony/Component/*\/Tests" to existing directory paths
     *
     * @param string[] $paths
     *
     * @return string[]
     */
    public function resolve_with_fnmatch(array $paths): array
    {
        $absolute_paths_found = [];
        foreach ($paths as $path) {
            if (strpos($path, '*') !== \false) {
                $found_paths = $this->found_in_glob($path);
                $absolute_paths_found = $this->append_paths($found_paths, $absolute_paths_found);
            } else {
                $absolute_paths_found = $this->append_paths([$path], $absolute_paths_found);
            }
        }
        return $absolute_paths_found;
    }
    /**
     * @param string[] $foundPaths
     * @param string[] $absolutePathsFound
     * @return string[]
     */
    private function append_paths(array $found_paths, array $absolute_paths_found): array
    {
        foreach ($found_paths as $found_path) {
            $found_path = realpath($found_path);
            if ($found_path === \false) {
                continue;
            }
            $absolute_paths_found[] = $found_path;
        }
        return $absolute_paths_found;
    }
    /**
     * @return string[]
     */
    private function found_in_glob(string $path): array
    {
        /** @var string[] $paths */
        $paths = (array) glob($path);
        return array_filter($paths, \Closure::from_callable('file_exists'));
    }
}