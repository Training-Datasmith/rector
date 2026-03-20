<?php

declare (strict_types=1);
namespace Rector\Php;

use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Exception\Configuration\Invalid_Configuration_Exception;
use Rector\Php\Php_Version_Resolver\Composer_Json_Php_Version_Resolver;
use Rector\Testing\Php_Unit\Static_Php_Unit_Environment;
use Rector\Util\String_Utils;
use Rector\Value_Object\Php_Version;
use ReflectionClass;
/**
 * @see \Rector\Tests\Php\PhpVersionProviderTest
 */
final class Php_Version_Provider
{
    /**
     * @see https://regex101.com/r/qBMnbl/1
     * @var string
     */
    private const VALID_PHP_VERSION_REGEX = '#^\d{5,6}$#';
    private ?int $php_version_features = null;
    /**
     * @return PhpVersion::*
     */
    public function provide(): int
    {
        if (Simple_Parameter_Provider::has_parameter(Option::PHP_VERSION_FEATURES)) {
            $this->php_version_features = Simple_Parameter_Provider::provide_int_parameter(Option::PHP_VERSION_FEATURES);
            $this->validate_php_version_features_parameter($this->php_version_features);
        }
        if ($this->php_version_features > 0) {
            return $this->php_version_features;
        }
        // for tests
        if (Static_Php_Unit_Environment::is_php_unit_run()) {
            // so we don't have to keep with up with newest version
            return Php_Version::PHP_10;
        }
        $project_composer_json = getcwd() . '/composer.json';
        if (file_exists($project_composer_json)) {
            $php_version = Composer_Json_Php_Version_Resolver::resolve($project_composer_json);
            if ($php_version !== null) {
                return $this->php_version_features = $php_version;
            }
        }
        // fallback to current PHP runtime version
        return $this->php_version_features = \PHP_VERSION_ID;
    }
    public function is_at_least_php_version(int $php_version): bool
    {
        return $php_version <= $this->provide();
    }
    /**
     * @param mixed $phpVersionFeatures
     */
    private function validate_php_version_features_parameter(int $php_version_features): void
    {
        if ($php_version_features === null) {
            return;
        }
        // get all constants
        $php_version_reflection_class = new ReflectionClass(Php_Version::class);
        // @todo check
        if (in_array($php_version_features, $php_version_reflection_class->get_constants(), \true)) {
            return;
        }
        if (!is_int($php_version_features)) {
            $this->throw_invalid_type_exception($php_version_features);
        }
        if (String_Utils::is_match((string) $php_version_features, self::VALID_PHP_VERSION_REGEX) && $php_version_features >= Php_Version::PHP_53 - 1) {
            return;
        }
        $this->throw_invalid_type_exception($php_version_features);
    }
    /**
     * @return never
     * @param mixed $phpVersionFeatures
     */
    private function throw_invalid_type_exception($php_version_features): void
    {
        $error_message = sprintf('Parameter "%s::%s" must be int, "%s" given.%sUse constant from "%s" to provide it, e.g. "%s::%s"', Option::class, 'PHP_VERSION_FEATURES', (string) $php_version_features, \PHP_EOL, Php_Version::class, Php_Version::class, 'PHP_80');
        throw new Invalid_Configuration_Exception($error_message);
    }
}