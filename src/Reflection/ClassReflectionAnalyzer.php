<?php

declare (strict_types=1);
namespace Rector\Reflection;

use Php_Stan\Reflection\Class_Reflection;
use Reflection_Enum;
final class Class_Reflection_Analyzer
{
    public function resolve_parent_class_name(Class_Reflection $class_reflection): ?string
    {
        $native_reflection = $class_reflection->get_native_reflection();
        if ($native_reflection instanceof Reflection_Enum) {
            return null;
        }
        return $native_reflection->get_parent_class_name();
    }
}