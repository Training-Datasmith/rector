<?php

declare (strict_types=1);
namespace Rector\Testing\Fixture;

use Rector_Prefix202603\Nette\Utils\File_System;
final class Fixture_File_Updater
{
    /**
     * @api
     */
    public static function update_fixture_content(string $original_content, string $changed_content, string $fixture_file_path): void
    {
        if (!getenv('UPDATE_TESTS') && !getenv('UT')) {
            return;
        }
        $new_original_content = self::resolve_new_fixture_content($original_content, $changed_content);
        File_System::write($fixture_file_path, $new_original_content, null);
    }
    private static function resolve_new_fixture_content(string $original_content, string $changed_content): string
    {
        if ($original_content === $changed_content) {
            return $original_content;
        }
        return $original_content . '-----' . \PHP_EOL . $changed_content;
    }
}