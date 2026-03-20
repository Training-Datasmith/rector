<?php

declare (strict_types=1);
namespace Rector\Util;

use Rector\Value_Object\Php_Version;
final class Php_Version_Factory
{
    /**
     * @return PhpVersion::*
     */
    public static function create_int_version(string $version): int
    {
        $explode_dash = explode('-', $version);
        if (count($explode_dash) > 1) {
            $version = $explode_dash[0];
        }
        $explode_version = explode('.', $version);
        $count_exploded_version = count($explode_version);
        if ($count_exploded_version >= 2) {
            return (int) $explode_version[0] * 10000 + (int) $explode_version[1] * 100;
        }
        return (int) $version;
    }
}