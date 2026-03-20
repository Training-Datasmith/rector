<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node\Stmt\Class_;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Object_Type;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
final class Class_Manipulator
{
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Reflection_Provider $reflection_provider;
    public function __construct(Node_Name_Resolver $node_name_resolver, Reflection_Provider $reflection_provider)
    {
        $this->node_name_resolver = $node_name_resolver;
        $this->reflection_provider = $reflection_provider;
    }
    public function has_parent_method_or_interface(Object_Type $object_type, string $old_method): bool
    {
        if (!$this->reflection_provider->has_class($object_type->get_class_name())) {
            return \false;
        }
        $class_reflection = $this->reflection_provider->get_class($object_type->get_class_name());
        $ancestor_class_reflections = array_merge($class_reflection->get_parents(), $class_reflection->get_interfaces());
        foreach ($ancestor_class_reflections as $ancestor_class_reflection) {
            if (!$ancestor_class_reflection->has_method($old_method)) {
                continue;
            }
            return \true;
        }
        return \false;
    }
    /**
     * @api phpunit
     */
    public function has_trait(Class_ $class, string $desired_trait): bool
    {
        foreach ($class->get_trait_uses() as $trait_use) {
            foreach ($trait_use->traits as $trait_name) {
                if (!$this->node_name_resolver->is_name($trait_name, $desired_trait)) {
                    continue;
                }
                return \true;
            }
        }
        return \false;
    }
}