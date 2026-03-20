<?php

declare (strict_types=1);
namespace Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
/**
 * @api
 *
 * Mirrors
 * https://github.com/nikic/PHP-Parser/blob/d520bc9e1d6203c35a1ba20675b79a051c821a9e/lib/PhpParser/NodeVisitor/CloningVisitor.php
 */
final class Cloning_Php_Doc_Node_Visitor extends \Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor
{
    public function enter_node(Node $node): Node
    {
        $cloned_node = clone $node;
        if (!$cloned_node->has_attribute(Php_Doc_Attribute_Key::ORIG_NODE)) {
            $cloned_node->set_attribute(Php_Doc_Attribute_Key::ORIG_NODE, $node);
        }
        return $cloned_node;
    }
}