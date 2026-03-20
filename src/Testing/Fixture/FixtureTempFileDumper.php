<?php

declare (strict_types=1);
namespace Rector\Testing\Fixture;

use Rector_Prefix202603\Nette\Utils\File_System;
/**
 * @api used in tests
 */
final class Fixture_Temp_File_Dumper
{
    /**
     * @var string
     */
    public const TEMP_FIXTURE_DIRECTORY = '/rector/tests_fixture_';
    public static function dump(string $file_contents, string $suffix = 'php'): string
    {
        // the "php" suffix is important, because that will hook into \Rector\Application\FileProcessor\PhpFileProcessor
        $temporary_file_name = sys_get_temp_dir() . self::TEMP_FIXTURE_DIRECTORY . '/' . md5($file_contents) . '.' . $suffix;
        File_System::write($temporary_file_name, $file_contents, null);
        return $temporary_file_name;
    }
}