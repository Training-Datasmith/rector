<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Name;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Arg_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Func_Call) {
            return null;
        }
        if (!$node->name instanceof Name) {
            return null;
        }
        // has no args
        if ($node->is_first_class_callable()) {
            return null;
        }
        $func_call_name = $node->name->to_string();
        foreach ($node->get_args() as $arg) {
            $arg->value->set_attribute(Attribute_Key::FROM_FUNC_CALL_NAME, $func_call_name);
        }
        return null;
    }
}