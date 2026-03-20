<?php

declare (strict_types=1);
namespace Rector\Node_Collector\Scope_Resolver;

use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
final class Parent_Class_Scope_Resolver
{
    public function resolve_parent_class_name(Scope $scope): ?string
    {
        $parent_class_reflection = $this->resolve_parent_class_reflection($scope);
        if (!$parent_class_reflection instanceof Class_Reflection) {
            return null;
        }
        return $parent_class_reflection->get_name();
    }
    public function resolve_parent_class_reflection(Scope $scope): ?Class_Reflection
    {
        $class_reflection = $scope->get_class_reflection();
        if (!$class_reflection instanceof Class_Reflection) {
            return null;
        }
        return $class_reflection->get_parent_class();
    }
}