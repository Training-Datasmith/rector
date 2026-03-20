<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Assign_Op;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node_Visitor;
use Rector\Node_Analyzer\Property_Fetch_Analyzer;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Value_Object\Method_Name;
final class Property_Fetch_Assign_Manipulator
{
    /**
     * @readonly
     */
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    /**
     * @readonly
     */
    private Node_Name_Resolver $node_name_resolver;
    /**
     * @readonly
     */
    private Property_Fetch_Analyzer $property_fetch_analyzer;
    public function __construct(Simple_Callable_Node_Traverser $simple_callable_node_traverser, Node_Name_Resolver $node_name_resolver, Property_Fetch_Analyzer $property_fetch_analyzer)
    {
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
        $this->node_name_resolver = $node_name_resolver;
        $this->property_fetch_analyzer = $property_fetch_analyzer;
    }
    public function is_assigned_multiple_times_in_constructor(Class_ $class, Property $property): bool
    {
        $class_method = $class->get_method(Method_Name::CONSTRUCT);
        if (!$class_method instanceof Class_Method) {
            return \false;
        }
        $count = 0;
        $property_name = $this->node_name_resolver->get_name($property);
        $this->simple_callable_node_traverser->traverse_nodes_with_callable((array) $class_method->get_stmts(), function (Node $node) use ($property_name, &$count): ?int {
            // skip anonymous classes and inner function
            if ($node instanceof Class_ || $node instanceof Function_) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof Assign && !$node instanceof Assign_Op) {
                return null;
            }
            if (!$this->property_fetch_analyzer->is_local_property_fetch_name($node->var, $property_name)) {
                return null;
            }
            ++$count;
            if ($count === 2) {
                return Node_Visitor::STOP_TRAVERSAL;
            }
            return null;
        });
        return $count === 2;
    }
}