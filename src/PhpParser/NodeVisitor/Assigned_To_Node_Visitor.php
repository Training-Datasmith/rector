<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Array_Item;
use Php_Parser\Node\Expr\Assign;
use Php_Parser\Node\Expr\Assign_Op;
use Php_Parser\Node\Expr\Assign_Ref;
use Php_Parser\Node\Expr\List_;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
/**
 * Inspired by https://github.com/phpstan/phpstan-src/blob/1.7.x/src/Parser/NewAssignedToPropertyVisitor.php
 */
final class Assigned_To_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
{
    public function enter_node(Node $node): ?Node
    {
        if ($node instanceof Assign_Op) {
            $node->var->set_attribute(Attribute_Key::IS_ASSIGN_OP_VAR, \true);
            return null;
        }
        if ($node instanceof Assign_Ref) {
            $node->expr->set_attribute(Attribute_Key::IS_ASSIGN_REF_EXPR, \true);
            return null;
        }
        if (!$node instanceof Assign) {
            return null;
        }
        $node->var->set_attribute(Attribute_Key::IS_BEING_ASSIGNED, \true);
        if ($node->var instanceof List_) {
            foreach ($node->var->items as $item) {
                if ($item instanceof Array_Item) {
                    $item->value->set_attribute(Attribute_Key::IS_BEING_ASSIGNED, \true);
                }
            }
        }
        $node->expr->set_attribute(Attribute_Key::IS_ASSIGNED_TO, \true);
        if ($node->expr instanceof Assign) {
            $node->var->set_attribute(Attribute_Key::IS_MULTI_ASSIGN, \true);
            $node->expr->set_attribute(Attribute_Key::IS_MULTI_ASSIGN, \true);
            $node->expr->var->set_attribute(Attribute_Key::IS_ASSIGNED_TO, \true);
        }
        return null;
    }
}