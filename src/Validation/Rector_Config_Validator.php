<?php

declare (strict_types=1);
namespace Rector\Validation;

use Rector\Configuration\Option;
use Rector\Configuration\Parameter\Simple_Parameter_Provider;
use Rector\Exception\Should_Not_Happen_Exception;
final class Rector_Config_Validator
{
    /**
     * @param string[] $rectorClasses
     */
    public static function ensure_no_duplicated_classes(array $rector_classes): void
    {
        $duplicated_rector_classes = self::resolve_duplicated_values($rector_classes);
        if ($duplicated_rector_classes === []) {
            return;
        }
        throw new Should_Not_Happen_Exception('Following rules are registered twice: ' . implode(', ', $duplicated_rector_classes));
    }
    /**
     * @param mixed[] $skip
     */
    public static function ensure_rector_rules_exist(array $skip): void
    {
        $non_existing_rules = [];
        $skipped_rector_rules = [];
        foreach ($skip as $key => $value) {
            if (self::is_rector_class_value($key)) {
                if (class_exists($key)) {
                    $skipped_rector_rules[] = $key;
                } else {
                    $non_existing_rules[] = $key;
                }
                continue;
            }
            if (!self::is_rector_class_value($value)) {
                continue;
            }
            if (class_exists($value)) {
                $skipped_rector_rules[] = $value;
                continue;
            }
            $non_existing_rules[] = $value;
        }
        Simple_Parameter_Provider::add_parameter(Option::SKIPPED_RECTOR_RULES, $skipped_rector_rules);
        if ($non_existing_rules === []) {
            return;
        }
        $non_existing_rules_string = '';
        foreach ($non_existing_rules as $non_existing_rule) {
            $non_existing_rules_string .= ' * ' . $non_existing_rule . \PHP_EOL;
        }
        throw new Should_Not_Happen_Exception('These rules from "$rectorConfig->skip()" do not exist - remove them or fix their names:' . \PHP_EOL . $non_existing_rules_string);
    }
    /**
     * @param mixed $value
     */
    private static function is_rector_class_value($value): bool
    {
        // only validate string
        if (!is_string($value)) {
            return \false;
        }
        // not regex path
        if (strpos($value, '*') !== \false) {
            return \false;
        }
        // not if no Rector suffix
        if (substr_compare($value, 'Rector', -strlen('Rector')) !== 0) {
            return \false;
        }
        // not directory
        if (is_dir($value)) {
            return \false;
        }
        // not file
        return !is_file($value);
    }
    /**
     * @param string[] $values
     * @return string[]
     */
    private static function resolve_duplicated_values(array $values): array
    {
        $counted = array_count_values($values);
        $duplicates = [];
        foreach ($counted as $value => $count) {
            if ($count > 1) {
                $duplicates[] = $value;
            }
        }
        return array_unique($duplicates);
    }
}