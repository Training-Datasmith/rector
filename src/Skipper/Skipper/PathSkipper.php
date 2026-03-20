<?php

declare (strict_types=1);
namespace Rector\Skipper\Skipper;

use Rector\Skipper\Matcher\File_Info_Matcher;
use Rector\Skipper\Skip_Criteria_Resolver\Skipped_Paths_Resolver;
final class Path_Skipper
{
    /**
     * @readonly
     */
    private File_Info_Matcher $file_info_matcher;
    /**
     * @readonly
     */
    private Skipped_Paths_Resolver $skipped_paths_resolver;
    public function __construct(File_Info_Matcher $file_info_matcher, Skipped_Paths_Resolver $skipped_paths_resolver)
    {
        $this->file_info_matcher = $file_info_matcher;
        $this->skipped_paths_resolver = $skipped_paths_resolver;
    }
    public function should_skip(string $file_path): bool
    {
        $skipped_paths = $this->skipped_paths_resolver->resolve();
        return $this->file_info_matcher->does_file_info_match_patterns($file_path, $skipped_paths);
    }
}