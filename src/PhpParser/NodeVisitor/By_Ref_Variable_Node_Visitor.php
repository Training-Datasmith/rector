<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Assign_Ref;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Function_Like;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
final class By_Ref_Variable_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    /**
     * @readonly
     */
    private Simple_Callable_Node_Traverser $simple_callable_node_traverser;
    public function __construct(Simple_Callable_Node_Traverser $simple_callable_node_traverser)
    {
        $this->simple_callable_node_traverser = $simple_callable_node_traverser;
    }
    public function enter_node(Node $node): ?Node
    {
        if ($node instanceof Assign_Ref) {
            $node->expr->set_attribute(Attribute_Key::IS_BYREF_VAR, \true);
            return null;
        }
        if (!$node instanceof Function_Like) {
            return null;
        }
        $by_ref_variable_names = $this->resolve_closure_use_is_by_ref_attribute($node, []);
        $by_ref_variable_names = $this->resolve_param_is_by_ref_attribute($node, $by_ref_variable_names);
        $stmts = $node->get_stmts();
        if ($stmts === null) {
            return null;
        }
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($stmts, function (Node $sub_node) use (&$by_ref_variable_names): ?\Php_Parser\Node\Expr\Variable {
            if ($sub_node instanceof Closure) {
                $by_ref_variable_names = $this->resolve_closure_use_is_by_ref_attribute($sub_node, $by_ref_variable_names);
                return null;
            }
            if (!$sub_node instanceof Variable) {
                return null;
            }
            if (!in_array($sub_node->name, $by_ref_variable_names, \true)) {
                return null;
            }
            $sub_node->set_attribute(Attribute_Key::IS_BYREF_VAR, \true);
            return $sub_node;
        });
        return null;
    }
    /**
     * @param string[] $byRefVariableNames
     * @return string[]
     */
    private function resolve_param_is_by_ref_attribute(Function_Like $function_like, array $by_ref_variable_names): array
    {
        foreach ($function_like->get_params() as $param) {
            if ($param->by_ref && $param->var instanceof Variable && !$param->var->name instanceof Expr) {
                $param->var->set_attribute(Attribute_Key::IS_BYREF_VAR, \true);
                /** @var string $paramVarName */
                $param_var_name = $param->var->name;
                $by_ref_variable_names[] = $param_var_name;
            }
        }
        return $by_ref_variable_names;
    }
    /**
     * @param string[] $byRefVariableNames
     * @return string[]
     */
    private function resolve_closure_use_is_by_ref_attribute(Function_Like $function_like, array $by_ref_variable_names): array
    {
        if (!$function_like instanceof Closure) {
            return $by_ref_variable_names;
        }
        foreach ($function_like->uses as $closure_use) {
            if ($closure_use->by_ref && !$closure_use->var->name instanceof Expr) {
                $closure_use->var->set_attribute(Attribute_Key::IS_BYREF_VAR, \true);
                /** @var string $closureVarName */
                $closure_var_name = $closure_use->var->name;
                $by_ref_variable_names[] = $closure_var_name;
            }
        }
        return $by_ref_variable_names;
    }
}