<?php

declare (strict_types=1);
namespace Rector\Vendor_Locker\Node_Vendor_Locker;

use Php_Parser\Node\Stmt\Class_Method;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Extended_Function_Variant;
use Php_Stan\Type\Mixed_Type;
use Rector\Node_Analyzer\Magic_Class_Method_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Reflection\Reflection_Resolver;
final class Class_Method_Return_Vendor_Lock_Resolver
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
    private Magic_Class_Method_Analyzer $magic_class_method_analyzer;
    public function __construct(Node_Name_Resolver $node_name_resolver, Reflection_Resolver $reflection_resolver, Magic_Class_Method_Analyzer $magic_class_method_analyzer)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->reflection_resolver = $reflection_resolver;
        $this->magic_class_method_analyzer = $magic_class_method_analyzer;
    }
    public function is_vendor_locked(Class_Method $class_method): bool
    {
        if ($this->magic_class_method_analyzer->is_unsafe_overridden($class_method)) {
            return \true;
        }
        if ($class_method->is_private()) {
            return \false;
        }
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($class_method);
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        $method_name = $this->node_name_resolver->get_name($class_method);
        return $this->is_vendor_locked_by_ancestors($class_reflection, $method_name);
    }
    private function is_vendor_locked_by_ancestors(Class_Reflection $class_reflection, string $method_name): bool
    {
        foreach ($class_reflection->get_ancestors() as $ancestor_class_reflections) {
            if ($ancestor_class_reflections === $class_reflection) {
                continue;
            }
            $native_class_reflection = $ancestor_class_reflections->get_native_reflection();
            // this should avoid detecting @method as real method
            if (!$native_class_reflection->has_method($method_name)) {
                continue;
            }
            if (!$ancestor_class_reflections->has_native_method($method_name)) {
                continue;
            }
            $parent_class_method_reflection = $ancestor_class_reflections->get_native_method($method_name);
            $parameters_acceptor = $parent_class_method_reflection->get_variants()[0];
            if (!$parameters_acceptor instanceof Extended_Function_Variant) {
                continue;
            }
            // here we count only on strict types, not on docs
            return !$parameters_acceptor->get_native_return_type() instanceof Mixed_Type;
        }
        return \false;
    }
}