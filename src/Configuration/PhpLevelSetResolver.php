<?php

declare (strict_types=1);
namespace Rector\Configuration;

use Rector\Set\Value_Object\Set_List;
use Rector\Tests\Configuration\Php_Level_Set_Resolver_Test;
use Rector\Value_Object\Php_Version;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @see PhpLevelSetResolverTest
 * @see \Rector\Tests\Configuration\PhpLevelSetResolverTest
 */
final class Php_Level_Set_Resolver
{
    /**
     * @var array<PhpVersion::*, SetList::PHP_*>
     */
    private const VERSION_LOWER_BOUND_CONFIGS = [Php_Version::PHP_52 => Set_List::PHP_52, Php_Version::PHP_53 => Set_List::PHP_53, Php_Version::PHP_54 => Set_List::PHP_54, Php_Version::PHP_55 => Set_List::PHP_55, Php_Version::PHP_56 => Set_List::PHP_56, Php_Version::PHP_70 => Set_List::PHP_70, Php_Version::PHP_71 => Set_List::PHP_71, Php_Version::PHP_72 => Set_List::PHP_72, Php_Version::PHP_73 => Set_List::PHP_73, Php_Version::PHP_74 => Set_List::PHP_74, Php_Version::PHP_80 => Set_List::PHP_80, Php_Version::PHP_81 => Set_List::PHP_81, Php_Version::PHP_82 => Set_List::PHP_82, Php_Version::PHP_83 => Set_List::PHP_83, Php_Version::PHP_84 => Set_List::PHP_84, Php_Version::PHP_85 => Set_List::PHP_85];
    /**
     * @param PhpVersion::* $phpVersion
     * @return string[]
     */
    public static function resolve_from_php_version(int $php_version): array
    {
        $config_file_paths = [];
        foreach (self::VERSION_LOWER_BOUND_CONFIGS as $version_lower_bound => $php_set_file_path) {
            if ($version_lower_bound <= $php_version) {
                $config_file_paths[] = $php_set_file_path;
            }
        }
        Assert::all_file_exists($config_file_paths);
        return $config_file_paths;
    }
}