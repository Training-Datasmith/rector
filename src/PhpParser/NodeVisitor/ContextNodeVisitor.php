<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Arg;
use Php_Parser\Node\Attribute;
use Php_Parser\Node\Expr\Array_;
use Php_Parser\Node\Expr\Array_Dim_Fetch;
use Php_Parser\Node\Expr\Binary_Op\Boolean_And;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node\Expr\Isset_;
use Php_Parser\Node\Expr\Post_Dec;
use Php_Parser\Node\Expr\Post_Inc;
use Php_Parser\Node\Expr\Pre_Dec;
use Php_Parser\Node\Expr\Pre_Inc;
use Php_Parser\Node\Expr\Property_Fetch;
use Php_Parser\Node\Expr\Static_Property_Fetch;
use Php_Parser\Node\Expr\Variable;
use Php_Parser\Node\Name\Fully_Qualified;
use Php_Parser\Node\Param;
use Php_Parser\Node\Stmt\Break_;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Do_;
use Php_Parser\Node\Stmt\Else_;
use Php_Parser\Node\Stmt\Else_If_;
use Php_Parser\Node\Stmt\For_;
use Php_Parser\Node\Stmt\Foreach_;
use Php_Parser\Node\Stmt\Function_;
use Php_Parser\Node\Stmt\If_;
use Php_Parser\Node\Stmt\Switch_;
use Php_Parser\Node\Stmt\Try_Catch;
use Php_Parser\Node\Stmt\Unset_;
use Php_Parser\Node\Stmt\While_;
use Php_Parser\Node_Visitor;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Node_Traverser\Simple_Node_Traverser;
final class Context_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
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
        if ($node instanceof For_ || $node instanceof Foreach_ || $node instanceof While_ || $node instanceof Do_ || $node instanceof Switch_) {
            $this->process_context_in_loop($node);
            return null;
        }
        if ($node instanceof Array_Dim_Fetch) {
            $this->process_inside_array_dim_fetch($node);
            return null;
        }
        if ($node instanceof Unset_) {
            foreach ($node->vars as $var) {
                $var->set_attribute(Attribute_Key::IS_UNSET_VAR, \true);
            }
            return null;
        }
        if ($node instanceof Try_Catch) {
            Simple_Node_Traverser::decorate_with_attribute_value($node->stmts, Attribute_Key::IS_IN_TRY_BLOCK, \true);
            return null;
        }
        if ($node instanceof Isset_) {
            foreach ($node->vars as $var) {
                $var->set_attribute(Attribute_Key::IS_ISSET_VAR, \true);
            }
            return null;
        }
        if ($node instanceof Attribute) {
            $this->process_context_in_attribute($node);
            return null;
        }
        if ($node instanceof If_ || $node instanceof Else_ || $node instanceof Else_If_) {
            $this->process_context_in_if($node);
            return null;
        }
        if ($node instanceof Arg) {
            $node->value->set_attribute(Attribute_Key::IS_ARG_VALUE, \true);
            return null;
        }
        if ($node instanceof Param) {
            $node->var->set_attribute(Attribute_Key::IS_PARAM_VAR, \true);
            return null;
        }
        if ($node instanceof Post_Dec || $node instanceof Post_Inc || $node instanceof Pre_Dec || $node instanceof Pre_Inc) {
            $node->var->set_attribute(Attribute_Key::IS_INCREMENT_OR_DECREMENT, \true);
            return null;
        }
        if ($node instanceof Boolean_And) {
            $node->right->set_attribute(Attribute_Key::IS_RIGHT_AND, \true);
            return null;
        }
        $this->process_context_in_class($node);
        return null;
    }
    private function process_inside_array_dim_fetch(Array_Dim_Fetch $array_dim_fetch): void
    {
        if ($array_dim_fetch->var instanceof Property_Fetch || $array_dim_fetch->var instanceof Static_Property_Fetch) {
            $array_dim_fetch->var->set_attribute(Attribute_Key::INSIDE_ARRAY_DIM_FETCH, \true);
        }
    }
    private function process_context_in_class(Node $node): void
    {
        if ($node instanceof Class_) {
            if ($node->extends instanceof Fully_Qualified) {
                $node->extends->set_attribute(Attribute_Key::IS_CLASS_EXTENDS, \true);
            }
            foreach ($node->implements as $implement) {
                $implement->set_attribute(Attribute_Key::IS_CLASS_IMPLEMENT, \true);
            }
        }
    }
    private function process_context_in_attribute(Attribute $attribute): void
    {
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($attribute->args, static function (Node $sub_node) {
            if ($sub_node instanceof Array_) {
                $sub_node->set_attribute(Attribute_Key::IS_ARRAY_IN_ATTRIBUTE, \true);
            }
            if ($sub_node instanceof Closure) {
                $sub_node->set_attribute(Attribute_Key::IS_CLOSURE_IN_ATTRIBUTE, \true);
            }
            return null;
        });
    }
    /**
     * @param \PhpParser\Node\Stmt\If_|\PhpParser\Node\Stmt\Else_|\PhpParser\Node\Stmt\ElseIf_ $node
     */
    private function process_context_in_if($node): void
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Break_) {
                $stmt->set_attribute(Attribute_Key::IS_IN_IF, \true);
            }
        }
    }
    /**
     * @param \PhpParser\Node\Stmt\For_|\PhpParser\Node\Stmt\Foreach_|\PhpParser\Node\Stmt\While_|\PhpParser\Node\Stmt\Do_|\PhpParser\Node\Stmt\Switch_ $node
     */
    private function process_context_in_loop($node): void
    {
        if ($node instanceof Foreach_) {
            if ($node->key_var instanceof Variable) {
                $node->key_var->set_attribute(Attribute_Key::IS_VARIABLE_LOOP, \true);
            }
            $node->value_var->set_attribute(Attribute_Key::IS_VARIABLE_LOOP, \true);
        }
        $stmts = $node instanceof Switch_ ? $node->cases : $node->stmts;
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($stmts, static function (Node $sub_node): ?int {
            if ($sub_node instanceof Class_ || $sub_node instanceof Function_ || $sub_node instanceof Closure) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if ($sub_node instanceof If_ || $sub_node instanceof Break_) {
                $sub_node->set_attribute(Attribute_Key::IS_IN_LOOP_OR_SWITCH, \true);
            }
            return null;
        });
    }
}