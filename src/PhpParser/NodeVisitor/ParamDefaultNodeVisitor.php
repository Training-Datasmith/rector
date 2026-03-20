<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Param;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Parser\Node_Traverser\Simple_Node_Traverser;
final class Param_Default_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    public function enter_node(Node $node): ?Node
    {
        if (!$node instanceof Param) {
            return null;
        }
        if (!$node->default instanceof Expr) {
            return null;
        }
        Simple_Node_Traverser::decorate_with_attribute_value($node->default, Attribute_Key::IS_PARAM_DEFAULT, \true);
        return null;
    }
}