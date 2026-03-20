<?php

declare (strict_types=1);
namespace Rector\Skipper;

use Rector\Skipper\File_System\Path_Normalizer;
final class Realpath_Matcher
{
    public function match(string $matching_path, string $file_path): bool
    {
        $real_path_matching_path = realpath($matching_path);
        if ($real_path_matching_path === \false) {
            return \false;
        }
        $realpath_file_path = realpath($file_path);
        if ($realpath_file_path === \false) {
            return \false;
        }
        $normalized_matching_path = Path_Normalizer::normalize($real_path_matching_path);
        $normalized_file_path = Path_Normalizer::normalize($realpath_file_path);
        // skip define direct path exactly equal
        if ($normalized_matching_path === $normalized_file_path) {
            return \true;
        }
        // ensure add / suffix to ensure no same prefix directory
        $suffixed_matching_path = rtrim($normalized_matching_path, '/') . '/';
        return strncmp($normalized_file_path, $suffixed_matching_path, strlen($suffixed_matching_path)) === 0;
    }
}