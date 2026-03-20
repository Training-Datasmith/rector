<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Stmt\Class_Const;
use Php_Parser\Node\Stmt\Property;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Parser\Node_Traverser\Simple_Node_Traverser;
final class Property_Or_Class_Const_Default_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    public function enter_node(Node $node): ?Node
    {
        if ($node instanceof Property) {
            foreach ($node->props as $property_item) {
                $default = $property_item->default;
                if (!$default instanceof Expr) {
                    continue;
                }
                Simple_Node_Traverser::decorate_with_attribute_value($default, Attribute_Key::IS_DEFAULT_PROPERTY_VALUE, \true);
            }
        }
        if ($node instanceof Class_Const) {
            foreach ($node->consts as $const) {
                Simple_Node_Traverser::decorate_with_attribute_value($const->value, Attribute_Key::IS_CLASS_CONST_VALUE, \true);
            }
        }
        return null;
    }
}