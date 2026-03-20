<?php

declare (strict_types=1);
namespace Rector\Testing\Fixture;

use Iterator;
use Rector_Prefix202603\Symfony\Component\Finder\Finder;
final class Fixture_File_Finder
{
    /**
     * @api used in tests
     * @return Iterator<array<int, string>>
     */
    public static function yield_directory(string $directory, string $suffix = '*.php.inc'): Iterator
    {
        $finder = (new Finder())->in($directory)->files()->name($suffix)->sort_by_name();
        foreach ($finder as $file_info) {
            yield [$file_info->get_real_path()];
        }
    }
}