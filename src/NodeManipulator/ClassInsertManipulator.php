<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Property;
use Php_Stan\Type\Type;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Parser\Node\Node_Factory;
final class Class_Insert_Manipulator
{
    /**
     * @readonly
     */
    private Node_Factory $node_factory;
    public function __construct(Node_Factory $node_factory)
    {
        $this->node_factory = $node_factory;
    }
    /**
     * @api
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\ClassMethod $addedStmt
     */
    public function add_as_first_method(Class_ $class, $added_stmt): void
    {
        $scope = $class->get_attribute(Attribute_Key::SCOPE);
        $added_stmt->set_attribute(Attribute_Key::SCOPE, $scope);
        // no stmts? add this one
        if ($class->stmts === []) {
            $class->stmts[] = $added_stmt;
            return;
        }
        $new_class_stmts = [];
        $is_added = \false;
        foreach ($class->stmts as $key => $class_stmt) {
            $next_stmt = $class->stmts[$key + 1] ?? null;
            if ($is_added === \false) {
                // first class method
                if ($class_stmt instanceof Class_Method) {
                    $new_class_stmts[] = $added_stmt;
                    $new_class_stmts[] = $class_stmt;
                    $is_added = \true;
                    continue;
                }
                // after last property
                if ($class_stmt instanceof Property && !$next_stmt instanceof Property) {
                    $new_class_stmts[] = $class_stmt;
                    $new_class_stmts[] = $added_stmt;
                    $is_added = \true;
                    continue;
                }
            }
            $new_class_stmts[] = $class_stmt;
        }
        // still not added? try after last trait
        // @todo
        if ($is_added) {
            $class->stmts = $new_class_stmts;
            return;
        }
        // keep added at least as first stmt
        $class->stmts = array_merge([$added_stmt], $class->stmts);
    }
    /**
     * @internal Use PropertyAdder service instead
     */
    public function add_property_to_class(Class_ $class, string $name, ?Type $type): void
    {
        $existing_property = $class->get_property($name);
        if ($existing_property instanceof Property) {
            return;
        }
        $property = $this->node_factory->create_private_property_from_name_and_type($name, $type);
        $this->add_as_first_method($class, $property);
    }
}