<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Stmt;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Static_;
use Php_Parser\Node_Visitor;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Enum\Node_Group;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Static_Variable_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
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
        if (!Node_Group::is_stmt_aware_node($node)) {
            return null;
        }
        Assert::property_exists($node, 'stmts');
        if ($node->stmts === null) {
            return null;
        }
        /** @var string[] $staticVariableNames */
        $static_variable_names = [];
        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Static_) {
                $this->set_is_static_var_attribute($stmt, $static_variable_names);
                continue;
            }
            foreach ($stmt->vars as $static_var) {
                $static_variable_name = $static_var->var->name;
                if (!is_string($static_variable_name)) {
                    continue;
                }
                $static_var->var->set_attribute(Attribute_Key::IS_STATIC_VAR, \true);
                $static_variable_names[] = $static_variable_name;
            }
        }
        return null;
    }
    /**
     * @param string[] $staticVariableNames
     */
    private function set_is_static_var_attribute(Stmt $stmt, array $static_variable_names): void
    {
        if ($static_variable_names === []) {
            return;
        }
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($stmt, static function (Node $sub_node) use ($static_variable_names) {
            if ($sub_node instanceof Class_) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$sub_node instanceof Variable) {
                return null;
            }
            if ($sub_node->name instanceof Expr) {
                return null;
            }
            if (!in_array($sub_node->name, $static_variable_names, \true)) {
                return null;
            }
            $sub_node->set_attribute(Attribute_Key::IS_STATIC_VAR, \true);
            return $sub_node;
        });
    }
}