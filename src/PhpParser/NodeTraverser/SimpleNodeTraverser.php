<?php

declare (strict_types=1);
namespace Rector\Php_Parser\Node_Traverser;

use Php_Parser\Node;
use Php_Parser\Node\Function_Like;
use Php_Parser\Node\Stmt\Class_;
use Php_Parser\Node_Traverser;
use Php_Parser\Node_Visitor;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Simple_Node_Traverser
{
    /**
     * @param Node[]|Node $nodesOrNode
     * @param AttributeKey::* $attributeKey
     * @param mixed $value
     */
    public static function decorate_with_attribute_value($nodes_or_node, string $attribute_key, $value): void
    {
        $callable_node_visitor = new class($attribute_key, $value) extends Node_Visitor_Abstract
        {
            /**
             * @readonly
             */
            private string $attribute_key;
            /**
             * @readonly
             * @var mixed
             */
            private $value;
            /**
             * @param mixed $value
             */
            public function __construct(string $attribute_key, $value)
            {
                $this->attribute_key = $attribute_key;
                $this->value = $value;
            }
            public function enter_node(Node $node): ?int
            {
                // avoid nested functions or classes
                if ($node instanceof Class_ || $node instanceof Function_Like) {
                    return Node_Visitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                }
                $node->set_attribute($this->attribute_key, $this->value);
                return null;
            }
        };
        $node_traverser = new Node_Traverser($callable_node_visitor);
        $nodes = $nodes_or_node instanceof Node ? [$nodes_or_node] : $nodes_or_node;
        $node_traverser->traverse($nodes);
    }
}