<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Closure;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Analyzer\Call_Like_Expects_This_Bound_Closure_Args_Analyzer;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Call_Like_This_Bound_Closure_Args_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    /**
     * @readonly
     */
    private Call_Like_Expects_This_Bound_Closure_Args_Analyzer $call_like_expects_this_binded_closure_args_analyzer;
    public function __construct(Call_Like_Expects_This_Bound_Closure_Args_Analyzer $call_like_expects_this_binded_closure_args_analyzer)
    {
        $this->call_like_expects_this_binded_closure_args_analyzer = $call_like_expects_this_binded_closure_args_analyzer;
    }
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Method_Call && !$node instanceof Static_Call && !$node instanceof Func_Call) {
            return null;
        }
        if ($node->is_first_class_callable()) {
            return null;
        }
        $args = $this->call_like_expects_this_binded_closure_args_analyzer->get_args_using_this_bound_closure($node);
        if ($args === []) {
            return null;
        }
        foreach ($args as $arg) {
            if ($arg->value instanceof Closure && !$arg->has_attribute(Attribute_Key::IS_CLOSURE_USES_THIS)) {
                $arg->value->set_attribute(Attribute_Key::IS_CLOSURE_USES_THIS, \true);
            }
        }
        return $node;
    }
}