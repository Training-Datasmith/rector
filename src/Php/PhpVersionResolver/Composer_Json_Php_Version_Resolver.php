<?php

declare (strict_types=1);
namespace Rector\Php\Php_Version_Resolver;

use Rector\Exception\Configuration\Invalid_Configuration_Exception;
use Rector\File_System\Json_File_System;
use Rector\Util\Php_Version_Factory;
use Rector\Value_Object\Php_Version;
use Rector_Prefix202603\Composer\Semver\Version_Parser;
/**
 * @see \Rector\Tests\Php\PhpVersionResolver\ComposerJsonPhpVersionResolver\ComposerJsonPhpVersionResolverTest
 */
final class Composer_Json_Php_Version_Resolver
{
    /**
     * @var array<string, PhpVersion::*|null>
     */
    private static array $cached_php_versions = [];
    /**
     * @return PhpVersion::*
     */
    public static function resolve_from_cwd_or_fail(): int
    {
        // use composer.json PHP version
        $project_composer_json_file_path = getcwd() . '/composer.json';
        if (file_exists($project_composer_json_file_path)) {
            $project_php_version = self::resolve($project_composer_json_file_path);
            if (is_int($project_php_version)) {
                return $project_php_version;
            }
        }
        throw new Invalid_Configuration_Exception(sprintf('We could not find local "composer.json" to determine your PHP version.%sPlease, fill the PHP version set in withPhpSets() manually.', \PHP_EOL));
    }
    /**
     * @return PhpVersion::*|null
     */
    public static function resolve(string $composer_json): ?int
    {
        if (array_key_exists($composer_json, self::$cached_php_versions)) {
            return self::$cached_php_versions[$composer_json];
        }
        $project_composer_json = Json_File_System::read_file_path($composer_json);
        // give this one a priority, as more generic one. see https://github.com/composer/composer/issues/7914
        $require_php_version = $project_composer_json['require']['php'] ?? $project_composer_json['require']['php-64bit'] ?? null;
        if ($require_php_version !== null) {
            self::$cached_php_versions[$composer_json] = self::create_int_version_from_composer_version($require_php_version);
            return self::$cached_php_versions[$composer_json];
        }
        // see https://getcomposer.org/doc/06-config.md#platform
        $platform_php = $project_composer_json['config']['platform']['php'] ?? null;
        if ($platform_php !== null) {
            self::$cached_php_versions[$composer_json] = Php_Version_Factory::create_int_version($platform_php);
            return self::$cached_php_versions[$composer_json];
        }
        return self::$cached_php_versions[$composer_json] = null;
    }
    /**
     * @return PhpVersion::*
     */
    private static function create_int_version_from_composer_version(string $project_php_version): int
    {
        $version_parser = new Version_Parser();
        $constraint = $version_parser->parse_constraints($project_php_version);
        $lower_bound = $constraint->get_lower_bound();
        $lower_bound_version = $lower_bound->get_version();
        return Php_Version_Factory::create_int_version($lower_bound_version);
    }
}