<?php

declare (strict_types=1);
namespace Rector\Vendor_Locker;

use Php_Parser\Node;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Type;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Type_Comparator\Type_Comparator;
use Rector\Reflection\Class_Reflection_Analyzer;
use Rector\Reflection\Reflection_Resolver;
use Rector\Static_Type_Mapper\Static_Type_Mapper;
use Rector\Vendor_Locker\Exception\Unresolvable_Class_Exception;
final class Parent_Class_Method_Type_Override_Guard
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    /**
     * @readonly
     */
    private Type_Comparator $type_comparator;
    /**
     * @readonly
     */
    private Static_Type_Mapper $static_type_mapper;
    /**
     * @readonly
     */
    private Class_Reflection_Analyzer $class_reflection_analyzer;
    public function __construct(Node_Name_Resolver $node_name_resolver, Reflection_Resolver $reflection_resolver, Type_Comparator $type_comparator, Static_Type_Mapper $static_type_mapper, Class_Reflection_Analyzer $class_reflection_analyzer)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->reflection_resolver = $reflection_resolver;
        $this->type_comparator = $type_comparator;
        $this->static_type_mapper = $static_type_mapper;
        $this->class_reflection_analyzer = $class_reflection_analyzer;
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PHPStan\Reflection\MethodReflection $classMethod
     */
    public function has_parent_class_method($class_method): bool
    {
        try {
            $parent_class_method = $this->resolve_parent_class_method($class_method);
            return $parent_class_method instanceof Method_Reflection;
        } catch (Unresolvable_Class_Exception $exception) {
            // we don't know all involved parents,
            // marking as parent exists which usually means the method is guarded against overrides.
            return \true;
        }
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PHPStan\Reflection\MethodReflection $classMethod
     */
    public function get_parent_class_method($class_method): ?Method_Reflection
    {
        try {
            return $this->resolve_parent_class_method($class_method);
        } catch (Unresolvable_Class_Exception $exception) {
            return null;
        }
    }
    public function should_skip_return_type_change(Class_Method $class_method, Type $parent_type): bool
    {
        if (!$class_method->return_type instanceof Node) {
            return \false;
        }
        $current_return_type = $this->static_type_mapper->map_php_parser_node_php_stan_type($class_method->return_type);
        if ($this->type_comparator->is_subtype($current_return_type, $parent_type)) {
            return \true;
        }
        return $this->type_comparator->are_types_equal($current_return_type, $parent_type);
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PHPStan\Reflection\MethodReflection $classMethod
     */
    private function resolve_parent_class_method($class_method): ?Method_Reflection
    {
        // early got null on private method
        if ($class_method->is_private()) {
            return null;
        }
        $class_reflection = $class_method instanceof Class_Method ? $this->reflection_resolver->resolve_class_reflection($class_method) : $class_method->get_declaring_class();
        if (!$class_reflection instanceof Class_Reflection) {
            // we can't resolve the class, so we don't know.
            throw new Unresolvable_Class_Exception();
        }
        /** @var string $methodName */
        $method_name = $class_method instanceof Class_Method ? $this->node_name_resolver->get_name($class_method) : $class_method->get_name();
        $current_class_reflection = $class_reflection;
        while ($this->has_class_parent($current_class_reflection)) {
            $parent_class_reflection = $current_class_reflection->get_parent_class();
            if (!$parent_class_reflection instanceof Class_Reflection) {
                // per AST we have a parent class, but our reflection classes are not able to load its class definition/signature
                throw new Unresolvable_Class_Exception();
            }
            if ($parent_class_reflection->has_native_method($method_name)) {
                return $parent_class_reflection->get_native_method($method_name);
            }
            $current_class_reflection = $parent_class_reflection;
        }
        foreach ($class_reflection->get_interfaces() as $interface_reflection) {
            if (!$interface_reflection->has_native_method($method_name)) {
                continue;
            }
            return $interface_reflection->get_native_method($method_name);
        }
        foreach ($class_reflection->get_traits() as $trait_reflection) {
            if (!$trait_reflection->has_native_method($method_name)) {
                continue;
            }
            $method_reflection = $trait_reflection->get_native_method($method_name);
            // any signature on non abstract trait method can be overridden
            if ($method_reflection->is_abstract()) {
                return $method_reflection;
            }
            return null;
        }
        return null;
    }
    private function has_class_parent(Class_Reflection $class_reflection): bool
    {
        return $this->class_reflection_analyzer->resolve_parent_class_name($class_reflection) !== null;
    }
}