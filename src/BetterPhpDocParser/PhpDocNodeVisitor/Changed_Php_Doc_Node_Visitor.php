<?php

declare (strict_types=1);
namespace Rector\Better_Php_Doc_Parser\Php_Doc_Node_Visitor;

use Php_Stan\Php_Doc_Parser\Ast\Node;
use Rector\Better_Php_Doc_Parser\Value_Object\Php_Doc_Attribute_Key;
use Rector\Php_Doc_Parser\Php_Doc_Parser\Php_Doc_Node_Visitor\Abstract_Php_Doc_Node_Visitor;
final class Changed_Php_Doc_Node_Visitor extends Abstract_Php_Doc_Node_Visitor
{
    private bool $has_changed = \false;
    public function before_traverse(Node $node): void
    {
        $this->has_changed = \false;
    }
    public function enter_node(Node $node): ?Node
    {
        $orig_node = $node->get_attribute(Php_Doc_Attribute_Key::ORIG_NODE);
        if ($orig_node === null) {
            $this->has_changed = \true;
        }
        return null;
    }
    public function has_changed(): bool
    {
        return $this->has_changed;
    }
}