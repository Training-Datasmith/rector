<?php

declare (strict_types=1);
namespace Rector\Node_Manipulator;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Class_Method;
use Php_Parser\Node_Visitor;
use Rector\Node_Name_Resolver\Node_Name_Resolver;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
final class Class_Method_Property_Fetch_Manipulator
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
    private \Rector\Node_Manipulator\Function_Like_Manipulator $function_like_manipulator;
    public function __construct(Simple_Callable_Node_Traverser $simple_callable_node_traverser, Node_Name_Resolver $node_name_resolver, \Rector\Node_Manipulator\Function_Like_Manipulator $function_like_manipulator)
    {
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
        $this->node_name_resolver = $node_name_resolver;
        $this->function_like_manipulator = $function_like_manipulator;
    }
    /**
     * In case the property name is different to param name:
     *
     * E.g.:
     * (SomeType $anotherValue)
     * $this->value = $anotherValue;
     * ↓
     * (SomeType $anotherValue)
     */
    public function find_param_assign_to_property_name(Class_Method $class_method, string $property_name): ?Param
    {
        $assigned_param_name = null;
        $this->simple_callable_node_traverser->traverse_nodes_with_callable((array) $class_method->stmts, function (Node $node) use ($property_name, &$assigned_param_name): ?int {
            if ($node instanceof Class_) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof Assign) {
                return null;
            }
            if (!$node->var instanceof Property_Fetch && !$node->var instanceof Static_Property_Fetch) {
                return null;
            }
            if (!$this->node_name_resolver->is_name($node->var, $property_name)) {
                return null;
            }
            if ($node->expr instanceof Method_Call || $node->expr instanceof Static_Call) {
                return null;
            }
            $assigned_param_name = $this->node_name_resolver->get_name($node->expr);
            return Node_Visitor::STOP_TRAVERSAL;
        });
        /** @var string|null $assignedParamName */
        if ($assigned_param_name === null) {
            return null;
        }
        /** @var Param $param */
        foreach ($class_method->params as $param) {
            if (!$this->node_name_resolver->is_name($param, $assigned_param_name)) {
                continue;
            }
            return $param;
        }
        return null;
    }
    /**
     * E.g.:
     * $this->value = 1000;
     * ↓
     * (int $value)
     *
     * @return Expr[]
     */
    public function find_assigns_to_property_name(Class_Method $class_method, string $property_name): array
    {
        $assign_exprs = [];
        $param_names = $this->function_like_manipulator->resolve_param_names($class_method);
        $this->simple_callable_node_traverser->traverse_nodes_with_callable((array) $class_method->stmts, function (Node $node) use ($property_name, &$assign_exprs, $param_names): ?int {
            if ($node instanceof Class_) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof Assign) {
                return null;
            }
            if (!$node->var instanceof Property_Fetch && !$node->var instanceof Static_Property_Fetch) {
                return null;
            }
            if (!$this->node_name_resolver->is_name($node->var, $property_name)) {
                return null;
            }
            // skip param assigns
            if ($this->node_name_resolver->is_names($node->expr, $param_names)) {
                return null;
            }
            $assign_exprs[] = $node->expr;
            return null;
        });
        return $assign_exprs;
    }
}