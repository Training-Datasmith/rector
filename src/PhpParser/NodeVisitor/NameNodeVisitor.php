<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Const_Fetch;
use Php_Parser\Node\Expr\Func_Call;
use Php_Parser\Node\Expr\New_;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Name;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Name_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    public function enter_node(Node $node): ?Node
    {
        if ($node instanceof Func_Call && $node->name instanceof Name) {
            $node->name->set_attribute(Attribute_Key::IS_FUNCCALL_NAME, \true);
            return null;
        }
        if ($node instanceof Const_Fetch) {
            $node->name->set_attribute(Attribute_Key::IS_CONSTFETCH_NAME, \true);
            return null;
        }
        if ($node instanceof New_ && $node->class instanceof Name) {
            $node->class->set_attribute(Attribute_Key::IS_NEW_INSTANCE_NAME, \true);
            return null;
        }
        if (!$node instanceof Static_Call) {
            return null;
        }
        if (!$node->class instanceof Name) {
            return null;
        }
        $node->class->set_attribute(Attribute_Key::IS_STATICCALL_CLASS_NAME, \true);
        return null;
    }
}