<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Stan\Reflection\Class_Reflection;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Reflection\Reflection_Resolver;
use Rector\Value_Object\Method_Name;
final class Class_Method_Manipulator
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Reflection_Resolver $reflection_resolver;
    public function __construct(Node_Name_Resolver $node_name_resolver, Reflection_Resolver $reflection_resolver)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->reflection_resolver = $reflection_resolver;
    }
    public function is_named_constructor(Class_Method $class_method): bool
    {
        if (!$this->node_name_resolver->is_name($class_method, Method_Name::CONSTRUCT)) {
            return \false;
        }
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($class_method);
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        if ($class_method->is_private()) {
            return \true;
        }
        if ($class_reflection->is_final_by_keyword()) {
            return \false;
        }
        return $class_method->is_protected();
    }
    public function has_parent_method_or_interface_method(Class_ $class, string $method_name): bool
    {
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($class);
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        foreach ($class_reflection->get_parents() as $parent_class_reflection) {
            if ($parent_class_reflection->has_method($method_name)) {
                return \true;
            }
            if ($parent_class_reflection->has_method(Method_Name::CALL)) {
                return \true;
            }
        }
        foreach ($class_reflection->get_interfaces() as $interface_reflection) {
            if ($interface_reflection->has_method($method_name)) {
                return \true;
            }
        }
        return \false;
    }
}