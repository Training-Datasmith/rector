<?php

declare (strict_types=1);
namespace Rector\Skipper;

final class Fnmatcher
{
    public function match(string $matching_path, string $file_path): bool
    {
        if (\fnmatch($matching_path, $file_path)) {
            return \true;
        }
        // in case of relative compare
        return \fnmatch('*/' . $matching_path, $file_path);
    }
}