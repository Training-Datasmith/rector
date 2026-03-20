<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node\Function_Like;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node\Stmt\Return_;
use Php_Parser\Node_Visitor;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Contract\Php_Parser\Decorating_Node_Visitor_Interface;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
use Rector\Php_Doc_Parser\Node_Traverser\Simple_Callable_Node_Traverser;
use Rector\Php_Parser\Node_Traverser\Simple_Node_Traverser;
final class By_Ref_Return_Node_Visitor extends Node_Visitor_Abstract implements Decorating_Node_Visitor_Interface
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
        if (!$node instanceof Function_Like) {
            return null;
        }
        if (!$node->returns_by_ref()) {
            return null;
        }
        $stmts = $node->get_stmts();
        if ($stmts === null) {
            return null;
        }
        Simple_Node_Traverser::decorate_with_attribute_value($stmts, Attribute_Key::IS_INSIDE_BYREF_FUNCTION_LIKE, \true);
        $this->simple_callable_node_traverser->traverse_nodes_with_callable($stmts, static function (Node $node) {
            // avoid nested functions or classes
            if ($node instanceof Class_ || $node instanceof Function_Like) {
                return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
            }
            if (!$node instanceof Return_) {
                return null;
            }
            $node->set_attribute(Attribute_Key::IS_BYREF_RETURN, \true);
            return $node;
        });
        return null;
    }
}