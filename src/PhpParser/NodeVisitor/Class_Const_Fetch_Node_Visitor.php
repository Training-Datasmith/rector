<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Identifier;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Class_Const_Fetch_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Class_Const_Fetch) {
            return null;
        }
        // pass value metadata to class node
        if (!$node->name instanceof Identifier) {
            return null;
        }
        $node->class->set_attribute(Attribute_Key::CLASS_CONST_FETCH_NAME, $node->name->to_string());
        return null;
    }
}