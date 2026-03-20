<?php

declare (strict_types=1);
namespace Rector\Skipper\Matcher;

use Rector\Skipper\File_System\Fn_Match_Path_Normalizer;
use Rector\Skipper\File_System\Path_Normalizer;
use Rector\Skipper\Fnmatcher;
use Rector\Skipper\Realpath_Matcher;
final class File_Info_Matcher
{
    /**
     * @readonly
     */
    private Fn_Match_Path_Normalizer $fn_match_path_normalizer;
    /**
     * @readonly
     */
    private Fnmatcher $fnmatcher;
    /**
     * @readonly
     */
    private Realpath_Matcher $realpath_matcher;
    public function __construct(Fn_Match_Path_Normalizer $fn_match_path_normalizer, Fnmatcher $fnmatcher, Realpath_Matcher $realpath_matcher)
    {
        $this->fn_match_path_normalizer = $fn_match_path_normalizer;
        $this->fnmatcher = $fnmatcher;
        $this->realpath_matcher = $realpath_matcher;
    }
    /**
     * @param string[] $filePatterns
     */
    public function does_file_info_match_patterns(string $file_path, array $file_patterns): bool
    {
        $file_path = Path_Normalizer::normalize($file_path);
        foreach ($file_patterns as $file_pattern) {
            $file_pattern = Path_Normalizer::normalize($file_pattern);
            if ($this->does_file_match_pattern($file_path, $file_pattern)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Supports both relative and absolute $file path. They differ for PHP-CS-Fixer and PHP_CodeSniffer.
     */
    private function does_file_match_pattern(string $file_path, string $ignored_path): bool
    {
        // in rector.php, the path can be absolute
        if ($file_path === $ignored_path) {
            return \true;
        }
        $ignored_path = $this->fn_match_path_normalizer->normalize_for_fnmatch($ignored_path);
        if ($ignored_path === '') {
            return \false;
        }
        if (strncmp($file_path, $ignored_path, strlen($ignored_path)) === 0) {
            return \true;
        }
        if (substr_compare($file_path, $ignored_path, -strlen($ignored_path)) === 0) {
            return \true;
        }
        if ($this->fnmatcher->match($ignored_path, $file_path)) {
            return \true;
        }
        return $this->realpath_matcher->match($ignored_path, $file_path);
    }
}