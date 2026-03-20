<?php

declare (strict_types=1);
namespace Rector\Skipper\Skipper;

use Rector\Skipper\Matcher\File_Info_Matcher;
final class Skip_Skipper
{
    /**
     * @readonly
     */
    private File_Info_Matcher $file_info_matcher;
    public function __construct(File_Info_Matcher $file_info_matcher)
    {
        $this->file_info_matcher = $file_info_matcher;
    }
    /**
     * @param array<string, string[]|null> $skippedClasses
     * @param object|string $checker
     */
    public function does_match_skip($checker, string $file_path, array $skipped_classes): bool
    {
        foreach ($skipped_classes as $skipped_class => $skipped_files) {
            if (!is_a($checker, $skipped_class, \true)) {
                continue;
            }
            // skip everywhere
            if (!is_array($skipped_files)) {
                return \true;
            }
            if ($this->file_info_matcher->does_file_info_match_patterns($file_path, $skipped_files)) {
                return \true;
            }
        }
        return \false;
    }
}