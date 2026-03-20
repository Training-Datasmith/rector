<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Stmt\Class_Const;
use Php_Parser\Node\Stmt\Class_Like;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Type\Object_Type;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Node_Type_Resolver\Node_Type_Resolver;
use Rector\Php_Parser\Ast_Resolver;
use Rector\Php_Parser\Node\Better_Node_Finder;
final class Class_Const_Manipulator
{
    /**
     * @readonly
     */
    private Better_Node_Finder $better_node_finder;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Ast_Resolver $ast_resolver;
    /**
     * @readonly
     */
    private Node_Type_Resolver $node_type_resolver;
    public function __construct(Better_Node_Finder $better_node_finder, Node_Name_Resolver $node_name_resolver, Ast_Resolver $ast_resolver, Node_Type_Resolver $node_type_resolver)
    {
        $this->better_node_finder = $better_node_finder;
        $this->node_name_resolver = $node_name_resolver;
        $this->ast_resolver = $ast_resolver;
        $this->node_type_resolver = $node_type_resolver;
    }
    public function has_class_const_fetch(Class_Const $class_const, Class_Reflection $class_reflection): bool
    {
        if (!$class_reflection->is_class() && !$class_reflection->is_enum()) {
            return \true;
        }
        $class_name = $class_reflection->get_name();
        $object_type = new Object_Type($class_name);
        foreach ($class_reflection->get_ancestors() as $ancestor_class_reflection) {
            $ancestor_class = $this->ast_resolver->resolve_class_from_class_reflection($ancestor_class_reflection);
            if (!$ancestor_class instanceof Class_Like) {
                continue;
            }
            // has in class?
            $is_class_const_fetch_found = (bool) $this->better_node_finder->find_first($ancestor_class, function (Node $node) use ($class_const, $class_name, $object_type): bool {
                // property + static fetch
                if (!$node instanceof Class_Const_Fetch) {
                    return \false;
                }
                return $this->is_name_match($node, $class_const, $class_name, $object_type);
            });
            if ($is_class_const_fetch_found) {
                return \true;
            }
        }
        return \false;
    }
    private function is_name_match(Class_Const_Fetch $class_const_fetch, Class_Const $class_const, string $class_name, Object_Type $object_type): bool
    {
        $class_const_name = (string) $this->node_name_resolver->get_name($class_const);
        $self_constant_name = 'self::' . $class_const_name;
        $static_constant_name = 'static::' . $class_const_name;
        $class_name_constant_name = $class_name . '::' . $class_const_name;
        if ($this->node_name_resolver->is_names($class_const_fetch, [$self_constant_name, $static_constant_name, $class_name_constant_name])) {
            return \true;
        }
        if ($this->node_type_resolver->is_object_type($class_const_fetch->class, $object_type)) {
            if (!$class_const_fetch->name instanceof Identifier) {
                return \true;
            }
            return $this->node_name_resolver->is_name($class_const_fetch->name, $class_const_name);
        }
        return \false;
    }
}