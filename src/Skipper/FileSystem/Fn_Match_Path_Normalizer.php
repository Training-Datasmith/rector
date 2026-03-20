<?php

declare (strict_types=1);
namespace Rector\Skipper\File_System;

/**
 * @see \Rector\Tests\Skipper\FileSystem\FnMatchPathNormalizerTest
 */
final class Fn_Match_Path_Normalizer
{
    public function normalize_for_fnmatch(string $path): string
    {
        if (substr_compare($path, '*', -strlen('*')) === 0 || strncmp($path, '*', strlen('*')) === 0) {
            return '*' . trim($path, '*') . '*';
        }
        if (strpos($path, '..') !== \false) {
            $real_path = realpath($path);
            if ($real_path === \false) {
                return '';
            }
            return \Rector\Skipper\File_System\Path_Normalizer::normalize($real_path);
        }
        return $path;
    }
}