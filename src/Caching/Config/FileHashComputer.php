<?php

declare (strict_types=1);
namespace Rector\Caching\Config;

use Rector\Application\Version_Resolver;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Exception\Should_Not_Happen_Exception;
/**
 * Inspired by https://github.com/symplify/easy-coding-standard/blob/e598ab54686e416788f28fcfe007fd08e0f371d9/packages/changed-files-detector/src/FileHashComputer.php
 */
final class File_Hash_Computer
{
    public function compute(string $file_path): string
    {
        $this->ensure_is_php($file_path);
        $parameters_hash = Simple_Parameter_Provider::hash();
        return sha1($file_path . $parameters_hash . Version_Resolver::PACKAGE_VERSION);
    }
    private function ensure_is_php(string $file_path): void
    {
        $file_extension = pathinfo($file_path, \PATHINFO_EXTENSION);
        if ($file_extension === 'php') {
            return;
        }
        throw new Should_Not_Happen_Exception(sprintf(
            // getRealPath() cannot be used, as it breaks in phar
            'Provide only PHP file, ready for Dependency Injection. "%s" given',
            $file_path
        ));
    }
}