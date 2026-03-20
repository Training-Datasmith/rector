<?php

declare (strict_types=1);
namespace Rector\Skipper\File_System;

final class Path_Normalizer
{
    public static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}