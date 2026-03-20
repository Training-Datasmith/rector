<?php

declare (strict_types=1);
namespace Rector\File_System;

use Rector_Prefix202603\Nette\Utils\File_System;
use Rector_Prefix202603\Nette\Utils\Json;
final class Json_File_System
{
    /**
     * @return array<string, mixed>
     */
    public static function read_file_path(string $file_path): array
    {
        $file_contents = File_System::read($file_path);
        return Json::decode($file_contents, \true);
    }
    /**
     * @param array<string, mixed> $data
     */
    public static function write_file(string $file_path, array $data): void
    {
        $json = Json::encode($data, \true);
        File_System::write($file_path, $json, null);
    }
}