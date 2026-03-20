<?php

declare (strict_types=1);
namespace Rector\Testing\Fixture;

use Rector_Prefix202603\Nette\Utils\File_System;
/**
 * @api
 */
final class Fixture_Splitter
{
    public static function contains_split(string $fixture_file_content): bool
    {
        return strpos($fixture_file_content, "-----\n") !== \false || strpos($fixture_file_content, "-----\r\n") !== \false;
    }
    /**
     * @return array<int, string>
     */
    public static function split(string $file_path): array
    {
        $fixture_file_contents = File_System::read($file_path);
        return self::split_fixture_file_contents($fixture_file_contents);
    }
    /**
     * @return array<int, string>
     */
    public static function split_fixture_file_contents(string $fixture_file_contents): array
    {
        $fixture_file_contents = str_replace("\r\n", "\n", $fixture_file_contents);
        return explode("-----\n", $fixture_file_contents);
    }
}