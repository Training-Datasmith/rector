<?php

declare (strict_types=1);
namespace Rector\Family_Tree\Node_Analyzer;

use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Reflection\Php\Php_Method_Reflection;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Type;
final class Class_Child_Analyzer
{
    /**
     * Look both parent class and interface, yes, all PHP interface methods are abstract
     */
    public function has_abstract_parent_class_method(Class_Reflection $class_reflection, string $method_name): bool
    {
        $parent_class_methods = $this->resolve_parent_class_methods($class_reflection, $method_name);
        if ($parent_class_methods === []) {
            return \false;
        }
        foreach ($parent_class_methods as $parent_class_method) {
            if ($parent_class_method->is_abstract()) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @api downgrade
     */
    public function resolve_parent_class_method_return_type(Class_Reflection $class_reflection, string $method_name): Type
    {
        $parent_class_methods = $this->resolve_parent_class_methods($class_reflection, $method_name);
        if ($parent_class_methods === []) {
            return new Mixed_Type();
        }
        foreach ($parent_class_methods as $parent_class_method) {
            $parameters_acceptor = Parameters_Acceptor_Selector::combine_acceptors($parent_class_method->get_variants());
            $native_return_type = $parameters_acceptor->get_native_return_type();
            if (!$native_return_type instanceof Mixed_Type) {
                return $native_return_type;
            }
        }
        return new Mixed_Type();
    }
    /**
     * @return PhpMethodReflection[]
     */
    private function resolve_parent_class_methods(Class_Reflection $class_reflection, string $method_name): array
    {
        if ($class_reflection->has_native_method($method_name) && $class_reflection->get_native_method($method_name)->is_private()) {
            return [];
        }
        $parent_class_methods = [];
        $parents = array_merge($class_reflection->get_parents(), $class_reflection->get_interfaces());
        foreach ($parents as $parent) {
            if (!$parent->has_native_method($method_name)) {
                continue;
            }
            $method_reflection = $parent->get_native_method($method_name);
            if (!$method_reflection instanceof Php_Method_Reflection) {
                continue;
            }
            $method_declaring_method_class = $method_reflection->get_declaring_class();
            if ($method_declaring_method_class->get_name() === $parent->get_name()) {
                $parent_class_methods[] = $method_reflection;
            }
        }
        return $parent_class_methods;
    }
}