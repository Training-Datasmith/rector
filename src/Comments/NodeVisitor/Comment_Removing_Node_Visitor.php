<?php

declare (strict_types=1);
namespace Rector\Comments\Node_Visitor;

use Php_Parser\Node;
use Php_Parser\Node_Visitor_Abstract;
use Rector\Node_Type_Resolver\Node\Attribute_Key;
final class Comment_Removing_Node_Visitor extends Node_Visitor_Abstract
{
    public function enter_node(Node $node): Node
    {
        // the node must be cloned, so original node is not touched in final print
        $cloned_node = clone $node;
        $cloned_node->set_attribute(Attribute_Key::COMMENTS, []);
        $cloned_node->set_attribute(Attribute_Key::PHP_DOC_INFO, null);
        return $cloned_node;
    }
}