<?php

declare (strict_types=1);
namespace Rector\Vendor_Locker\Node_Vendor_Locker;

use Php_Parser\Node\Stmt\Class_Method;
use Php_Stan\Reflection\Class_Reflection;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Reflection\Reflection_Resolver;
final class Class_Method_Param_Vendor_Lock_Resolver
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
    public function is_vendor_locked(Class_Method $class_method): bool
    {
        if ($class_method->is_magic()) {
            return \true;
        }
        if ($class_method->is_private()) {
            return \false;
        }
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($class_method);
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        /** @var string $methodName */
        $method_name = $this->node_name_resolver->get_name($class_method);
        // has interface vendor lock? → better skip it, as PHPStan has access only to just analyzed classes
        return $this->has_parent_interface_method($class_reflection, $method_name);
    }
    /**
     * Has interface even in our project?
     * Better skip it, as PHPStan has access only to just analyzed classes.
     * This might change type, that works for current class, but breaks another implementer.
     */
    private function has_parent_interface_method(Class_Reflection $class_reflection, string $method_name): bool
    {
        foreach ($class_reflection->get_interfaces() as $interface_class_reflection) {
            if ($interface_class_reflection->has_method($method_name)) {
                return \true;
            }
        }
        return \false;
    }
}