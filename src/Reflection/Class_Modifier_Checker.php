<?php

declare (strict_types=1);
namespace Rector\Reflection;

use Php_Parser\Node;
use Php_Stan\Reflection\Class_Reflection;
final class Class_Modifier_Checker
{
    /**
     * @readonly
     */
    private \Rector\Reflection\Reflection_Resolver $reflection_resolver;
    public function __construct(\Rector\Reflection\Reflection_Resolver $reflection_resolver)
    {
        $this->reflection_resolver = $reflection_resolver;
    }
    public function is_inside_final_class(Node $node): bool
    {
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($node);
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        return $class_reflection->is_final_by_keyword();
    }
    public function is_inside_abstract_class(Node $node): bool
    {
        $class_reflection = $this->reflection_resolver->resolve_class_reflection($node);
        if (!$class_reflection instanceof Class_Reflection) {
            return \false;
        }
        return $class_reflection->is_abstract();
    }
}