<?php

declare (strict_types=1);
namespace Rector\Configuration\Parameter;

use Rector\Configuration\Option;
use Rector\Exception\Should_Not_Happen_Exception;
use Rector_Prefix202603\Webmozart\Assert\Assert;
/**
 * @api
 */
final class Simple_Parameter_Provider
{
    /**
     * @var array<string, mixed>
     */
    private static array $parameters = [];
    /**
     * @param Option::* $name
     * @param mixed $value
     */
    public static function add_parameter(string $name, $value): void
    {
        if (is_array($value)) {
            $merged_parameters = array_merge(self::$parameters[$name] ?? [], $value);
            self::$parameters[$name] = $merged_parameters;
        } else {
            self::$parameters[$name][] = $value;
        }
    }
    /**
     * @param Option::* $name
     * @param mixed $value
     */
    public static function set_parameter(string $name, $value): void
    {
        self::$parameters[$name] = $value;
    }
    /**
     * @param Option::* $name
     * @return mixed[]
     */
    public static function provide_array_parameter(string $name): array
    {
        $parameter = self::$parameters[$name] ?? [];
        Assert::is_array($parameter);
        $array_is_list_function = function (array $array): bool {
            if (function_exists('array_is_list')) {
                return array_is_list($array);
            }
            if ($array === []) {
                return \true;
            }
            $current_key = 0;
            foreach ($array as $key => $noop) {
                if ($key !== $current_key) {
                    return \false;
                }
                ++$current_key;
            }
            return \true;
        };
        if ($array_is_list_function($parameter)) {
            // remove duplicates
            $unique_parameters = array_unique($parameter, \SORT_REGULAR);
            return array_values($unique_parameters);
        }
        return $parameter;
    }
    /**
     * @param Option::* $name
     */
    public static function has_parameter(string $name): bool
    {
        return array_key_exists($name, self::$parameters);
    }
    /**
     * @param Option::* $name
     */
    public static function provide_string_parameter(string $name, ?string $default = null): string
    {
        if ($default === null) {
            self::ensure_parameter_is_set($name);
        }
        return self::$parameters[$name] ?? $default;
    }
    public static function provide_int_parameter(string $key): int
    {
        return self::$parameters[$key];
    }
    /**
     * @param Option::* $name
     */
    public static function provide_bool_parameter(string $name, ?bool $default = null): bool
    {
        if ($default === null) {
            self::ensure_parameter_is_set($name);
        }
        return self::$parameters[$name] ?? $default;
    }
    /**
     * @api
     * For cache invalidation
     */
    public static function hash(): string
    {
        $parameter_keys = self::$parameters;
        return sha1(serialize($parameter_keys));
    }
    /**
     * @param Option::* $name
     */
    private static function ensure_parameter_is_set(string $name): void
    {
        if (array_key_exists($name, self::$parameters)) {
            return;
        }
        throw new Should_Not_Happen_Exception(sprintf('Parameter "%s" was not found', $name));
    }
}